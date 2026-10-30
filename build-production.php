<?php

declare(strict_types=1);

$source = __DIR__;
$target = $source . DIRECTORY_SEPARATOR . 'production';

$removeDirectory = static function (string $directory) use (&$removeDirectory): void {
    if (!is_dir($directory)) {
        return;
    }
    $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
    foreach ($items as $item) {
        $path = $item->getPathname();
        if ($item->isDir() && !$item->isLink()) {
            $removeDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($directory);
};

$removeDirectory($target);
mkdir($target, 0775, true);

$written = [];
$write = static function (string $relative, string $contents, bool $minifyPhp = false) use ($target, &$written): void {
    $destination = $target . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $directory = dirname($destination);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    if ($minifyPhp) {
        $contents = str_replace(
            "<!-- <?= \$component('central-vacuum') ?> -->",
            "<?php \$component('central-vacuum'); ?>",
            $contents,
        );
        $contents = preg_replace('/<!-- assistant build[\s\S]*?-->/', '', $contents) ?? $contents;
        $temporary = $destination . '.source';
        file_put_contents($temporary, $contents, LOCK_EX);
        $contents = php_strip_whitespace($temporary);
        unlink($temporary);
        $tokens = token_get_all($contents);
        $contents = '';
        foreach ($tokens as $token) {
            $text = is_array($token) ? $token[1] : $token;
            if (is_array($token) && $token[0] === T_INLINE_HTML) {
                $text = preg_replace('/<!--(?!\[if|<!)[\s\S]*?-->/', '', $text) ?? $text;
                $text = preg_replace_callback(
                    '/<(script|style)\\b([^>]*)>([\\s\\S]*?)<\\/\\1>/i',
                    static function (array $match): string {
                        $body = preg_replace('/\\/\\*[\\s\\S]*?\\*\\//', '', $match[3]) ?? $match[3];
                        return '<' . $match[1] . $match[2] . '>' . $body . '</' . $match[1] . '>';
                    },
                    $text,
                ) ?? $text;
            }
            $contents .= $text;
        }
    }
    file_put_contents($destination, $contents, LOCK_EX);
    $written[$relative] = strlen($contents);
};

$copy = static function (string $relative, bool $minifyPhp = false) use ($source, $write): void {
    $path = $source . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($path)) {
        throw new RuntimeException('Missing source file: ' . $relative);
    }
    $write($relative, (string) file_get_contents($path), $minifyPhp);
};

$copyTree = static function (string $directory, callable $accept, bool $minifyPhp = false) use ($source, $copy): void {
    $root = $source . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
    );
    $paths = [];
    foreach ($iterator as $item) {
        if (!$item->isFile() || $item->isLink()) {
            continue;
        }
        $relative = $directory . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $iterator->getSubPathname());
        if ($accept($relative)) {
            $paths[] = $relative;
        }
    }
    sort($paths);
    foreach ($paths as $relative) {
        $copy($relative, $minifyPhp && str_ends_with($relative, '.php'));
    }
};

$allPhp = static fn (string $relative): bool => str_ends_with($relative, '.php');
$copyTree('app', $allPhp, true);
$copyTree('config', $allPhp, true);
$copyTree('core', static fn (string $relative): bool => str_ends_with($relative, '.php') && !str_starts_with($relative, 'core/Console/'), true);
$copyTree('modules', $allPhp, true);
$copyTree('resources', $allPhp, true);
$copyTree('routes', $allPhp, true);
$copyTree('frontend', static fn (string $relative): bool => (bool) preg_match('/\.(?:css|js)$/', $relative));
$copy('bootstrap.php', true);
$copy('.htaccess');

foreach (['public/index.php' => true, 'public/.htaccess' => false, 'public/llms.txt' => false, 'public/.well-known/security.txt' => false, 'public/lufly-central.css' => false, 'public/lufly-central.js' => false, 'public/lufly-central-story.css' => false, 'public/lufly-central-story.js' => false] as $relative => $minifyPhp) {
    $copy($relative, $minifyPhp);
}

$connection = new PDO('sqlite:' . $source . '/database/lufly.sqlite', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$imageReferences = [];
foreach ([['media', 'path'], ['categories', 'image'], ['categories', 'icon'], ['collections', 'image'], ['brands', 'logo'], ['assistant_leads', 'image_path']] as [$table, $column]) {
    $where = '"' . $column . '" IS NOT NULL AND "' . $column . '" != \'\'';
    if ($table === 'media') {
        $where .= ' AND status != \'missing\'';
    }
    $statement = $connection->query('SELECT "' . $column . '" FROM "' . $table . '" WHERE ' . $where);
    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $path) {
        $path = ltrim((string) $path, '/');
        if (str_starts_with($path, 'images/')) {
            $imageReferences[$path] = true;
        }
    }
}

$imageRoot = $source . '/public/images';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($imageRoot, FilesystemIterator::SKIP_DOTS));
$imageCount = 0;
$imageBytes = 0;
$missing = [];
foreach ($imageReferences as $relative => $_) {
    if (!is_file($source . '/public/' . $relative)) {
        $missing[] = $relative;
    }
}
foreach ($iterator as $item) {
    if (!$item->isFile() || $item->isLink()) {
        continue;
    }
    $relative = 'images/' . str_replace(DIRECTORY_SEPARATOR, '/', $iterator->getSubPathname());
    $basename = basename($relative);
    if ($basename === '.gitkeep') {
        continue;
    }
    $isProductImage = str_starts_with($relative, 'images/products/');
    if ($isProductImage && !isset($imageReferences[$relative])) {
        continue;
    }
    $copy('public/' . $relative);
    $imageCount++;
    $imageBytes += $item->getSize();
}

$copy('storage/.htaccess');
foreach (['storage/cache', 'storage/logs', 'storage/uploads', 'storage/backups', 'storage/reports', 'public/uploads/assistant', 'public/images/deleted-images', 'public/images/uploads'] as $directory) {
    $path = $target . '/' . $directory;
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

$sql = (string) file_get_contents($source . '/lufly-database.sql');
$sqlLines = preg_split('/\R/', $sql) ?: [];
$sqlLines = array_values(array_filter($sqlLines, static fn (string $line): bool => !preg_match('/^\s*--/', $line)));
$write('lufly-database.sql', implode("\n", $sqlLines) . "\n");

$envLines = preg_split('/\R/', (string) file_get_contents($source . '/.env.example')) ?: [];
$env = [];
foreach ($envLines as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '#') || str_starts_with($trimmed, 'DB_SQLITE_PATH=')) {
        continue;
    }
    if (str_starts_with($trimmed, 'APP_ENV=')) {
        $trimmed = 'APP_ENV=production';
    } elseif (str_starts_with($trimmed, 'APP_DEBUG=')) {
        $trimmed = 'APP_DEBUG=false';
    } elseif (str_starts_with($trimmed, 'APP_URL=')) {
        $trimmed = 'APP_URL=';
    } elseif (str_starts_with($trimmed, 'DB_CONNECTION=')) {
        $trimmed = 'DB_CONNECTION=mysql';
    } elseif (str_starts_with($trimmed, 'DB_USERNAME=')) {
        $trimmed = 'DB_USERNAME=';
    }
    $env[] = $trimmed;
}
$write('.env.example', implode("\n", $env) . "\n");

$htaccess = $target . '/.htaccess';
$lines = preg_split('/\R/', (string) file_get_contents($htaccess)) ?: [];
$lines = array_values(array_filter($lines, static fn (string $line): bool => trim($line) !== '' && !str_starts_with(ltrim($line), '#')));
file_put_contents($htaccess, implode("\n", $lines) . "\n", LOCK_EX);
foreach (['public/.htaccess', 'storage/.htaccess', 'public/images/uploads/.htaccess'] as $relative) {
    $path = $target . '/' . $relative;
    if (!is_file($path)) {
        continue;
    }
    $lines = preg_split('/\R/', (string) file_get_contents($path)) ?: [];
    $lines = array_values(array_filter($lines, static fn (string $line): bool => trim($line) !== '' && !str_starts_with(ltrim($line), '#')));
    file_put_contents($path, implode("\n", $lines) . "\n", LOCK_EX);
}

$manifest = [
    'files' => count($written),
    'bytes' => array_sum($written),
    'images' => $imageCount,
    'image_bytes' => $imageBytes,
    'database_references' => count($imageReferences),
    'missing_database_images' => array_values($missing),
    'excluded' => ['.env', '.git', '.github', 'cli', 'server.php', 'database/lufly.sqlite', 'database/migrations', 'database/schema', 'database/seeders', 'core/Console', 'docs', 'node_modules'],
];
file_put_contents(sys_get_temp_dir() . '/lufly-production-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n", LOCK_EX);
echo json_encode($manifest, JSON_UNESCAPED_SLASHES) . "\n";
