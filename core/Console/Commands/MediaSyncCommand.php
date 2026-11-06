<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Database\Connection;
use Core\Database\DatabaseManager;

/**
 * Answers "the code arrived but the pictures did not" on the machine that
 * serves the site.
 *
 * A git-based deploy on shared hosting regularly lands the PHP files and
 * stops before `public/images/` (284 MB, 1,462 files): the clone/pull times
 * out, hits the disk quota, or is refused because the folder was also filled
 * by hand and the working tree is dirty. The database keeps pointing at
 * `/images/...`, the rows are fine, and every card renders without a picture.
 *
 * This command lists the image paths the database expects, tests each one on
 * this server's disk, and fetches the ones that are missing - either from
 * GitHub (raw.githubusercontent.com, same ref as the deployed code) or from a
 * folder that was uploaded by FTP.
 *
 *   php cli media:sync --dry-run                  # what is missing on this server
 *   php cli media:sync                            # fetch the missing files from GitHub
 *   php cli media:sync --limit=200                # fetch in batches
 *   php cli media:sync --from-dir=/home/u/images  # copy from an uploaded copy
 *   php cli media:sync --include-hidden           # hidden rows too (EMF drawings)
 *
 * Exit code 0 means every referenced image is on disk, 1 means something is
 * still missing, so it can gate a deploy script.
 */
final class MediaSyncCommand extends Command
{
    protected string $name = 'media:sync';

    protected string $description = 'Verify the images the database expects and fetch the ones missing on this server.';

    public function handle(array $args, array $options): int
    {
        $connection = $this->app->get(DatabaseManager::class)->connection();

        $dryRun = (bool) $this->option($options, 'dry-run', false);
        $fromDir = rtrim(trim((string) $this->option($options, 'from-dir', '')), '/');
        $ref = trim((string) $this->option($options, 'ref', (string) env('MEDIA_SYNC_REF', 'main')));
        $repo = trim((string) $this->option($options, 'repo', (string) env('MEDIA_SYNC_REPO', 'hossammohamed-GT/lufly_1')));
        $limit = array_key_exists('limit', $options) ? max(0, (int) $options['limit']) : 0;
        $includeHidden = (bool) $this->option($options, 'include-hidden', false);

        $this->line('driver: ' . $connection->driver());
        $this->line('media root: ' . base_path('public'));

        [$referenced, $statuses] = $this->referencedPaths($connection, $includeHidden);

        if ($referenced === []) {
            $this->error('No image paths in the database (run `php cli migrate` and import lufly-database.sql first).');

            return 1;
        }

        $missing = [];
        foreach (array_keys($referenced) as $path) {
            if (!$this->exists($path)) {
                $missing[$path] = $statuses[$path] ?? 'active';
            }
        }

        $present = count($referenced) - count($missing);

        $this->line(sprintf(
            'referenced images: %d   on disk: %d   missing here: %d',
            count($referenced),
            $present,
            count($missing),
        ));

        $orphans = $this->orphanCount($referenced);
        if ($orphans > 0) {
            $this->line(sprintf('files on disk the database does not reference: %d', $orphans));
        }

        if ($missing === []) {
            $this->success('Every image the database points at is on this server.');

            return 0;
        }

        if ($dryRun) {
            $this->info('Missing files (dry run, nothing was written):');
            foreach (array_slice(array_keys($missing), 0, 40) as $path) {
                $this->line('  ' . $path);
            }
            if (count($missing) > 40) {
                $this->line(sprintf('  ... and %d more', count($missing) - 40));
            }
            $this->line('');
            $this->line('Run `php cli media:sync` without --dry-run to fetch them.');

            return 1;
        }

        $queue = $limit > 0 ? array_slice(array_keys($missing), 0, $limit) : array_keys($missing);
        $target = $fromDir !== '' ? 'folder ' . $fromDir : sprintf('https://raw.githubusercontent.com/%s/%s', $repo, $ref);

        $this->info(sprintf('Fetching %d file(s) from %s', count($queue), $target));

        $done = 0;
        $failed = [];

        foreach ($queue as $path) {
            $ok = $fromDir !== ''
                ? $this->copyFrom($fromDir, $path)
                : $this->download($repo, $ref, $path);

            if ($ok) {
                $done++;
                $this->line('  ok  ' . $path);
            } else {
                $failed[] = $path;
                $this->error('  !!  ' . $path);
            }
        }

        $still = count($missing) - $done;

        $this->line('');
        $this->line(sprintf('copied: %d   failed: %d   still missing: %d', $done, count($failed), $still));

        if ($still === 0) {
            $this->success('Done - every referenced image is now on this server.');

            return 0;
        }

        if ($fromDir === '') {
            $this->line('If the host cannot reach GitHub, upload the folder and re-run with:');
            $this->line('  php cli media:sync --from-dir=/path/to/uploaded/images');
        }

        return 1;
    }

    /**
     * Every path the storefront will ask the web server for.
     *
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    private function referencedPaths(Connection $connection, bool $includeHidden): array
    {
        $whitelist = "('missing', 'hidden')";

        $sql = 'SELECT path, status FROM media WHERE path IS NOT NULL AND path <> \'\'';
        if (!$includeHidden) {
            $sql .= ' AND status NOT IN ' . $whitelist;
        }

        $referenced = [];
        $statuses = [];

        foreach ($connection->select($sql) as $row) {
            $path = $this->normalise((string) ($row['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $referenced[$path] = (string) ($row['status'] ?? 'active');
            $statuses[$path] = (string) ($row['status'] ?? 'active');
        }

        /* categories and collections carry their own image column, they are
           not written into media - the storefront renders them directly. */
        foreach (['categories', 'collections'] as $table) {
            try {
                foreach ($connection->select("SELECT image FROM {$table} WHERE image IS NOT NULL AND image <> ''") as $row) {
                    $path = $this->normalise((string) ($row['image'] ?? ''));
                    if ($path !== '') {
                        $referenced[$path] = $referenced[$path] ?? 'active';
                        $statuses[$path] = $statuses[$path] ?? 'active';
                    }
                }
            } catch (\Throwable) {
                /* a schema from an older install without the column: skip it */
            }
        }

        return [$referenced, $statuses];
    }

    private function normalise(string $path): string
    {
        $clean = ltrim(trim($path), '/');

        if ($clean === '' || str_contains($clean, '://')) {
            return '';
        }

        return $clean;
    }

    private function exists(string $path): bool
    {
        return is_file(base_path('public/' . $path));
    }

    /** @param array<string, string> $referenced */
    private function orphanCount(array $referenced): int
    {
        $root = base_path('public/images');
        if (!is_dir($root)) {
            return 0;
        }

        $known = array_fill_keys(array_keys($referenced), true);
        $orphans = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if ($name === '.gitkeep') {
                continue;
            }
            $relative = 'images/' . ltrim(str_replace($root, '', $file->getPathname()), '/');
            if (!isset($known[$relative])) {
                $orphans++;
            }
        }

        return $orphans;
    }

    private function copyFrom(string $fromDir, string $path): bool
    {
        $candidates = [
            $fromDir . '/' . $path,
            $fromDir . '/public/' . $path,
            $fromDir . '/' . basename($path),
        ];

        foreach ($candidates as $source) {
            if (!is_file($source)) {
                continue;
            }

            return $this->store($path, (string) file_get_contents($source));
        }

        return false;
    }

    private function download(string $repo, string $ref, string $path): bool
    {
        $url = sprintf('https://raw.githubusercontent.com/%s/%s/public/%s', $repo, $ref, $path);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => 'lufly-media-sync',
                'follow_location' => 1,
                'max_redirects' => 3,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if (!is_string($body) || $body === '') {
            return false;
        }

        /* raw.githubusercontent.com answers a wrong path with a short 404
           page and a 200-looking body on some hosts: never store HTML. */
        if (str_starts_with(ltrim($body), '<!DOCTYPE') || str_starts_with(ltrim($body), '<html')) {
            return false;
        }

        return $this->store($path, $body);
    }

    private function store(string $path, string $bytes): bool
    {
        if ($bytes === '') {
            return false;
        }

        $target = base_path('public/' . $path);
        $dir = dirname($target);

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $tmp = $target . '.part';
        if (@file_put_contents($tmp, $bytes) === false) {
            return false;
        }

        return @rename($tmp, $target);
    }
}
