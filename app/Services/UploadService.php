<?php

declare(strict_types=1);

namespace App\Services;

use Core\Exceptions\UploadException;
use Core\Foundation\Application;
use Core\Support\Str;

class UploadService
{
    public function __construct(private readonly Application $app)
    {
    }

    public function store(array $file, string $directory = 'general'): array
    {
        $this->assertValidUpload($file);

        $original = (string) ($file['name'] ?? 'file');
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $this->assertAllowedExtension($extension);

        $maxKb = (int) config('uploads.max_size_kb', 10240);
        $sizeKb = ((int) ($file['size'] ?? 0)) / 1024;
        if ($sizeKb > $maxKb) {
            throw new UploadException(trans('errors.upload_too_large', ['max' => (string) $maxKb]));
        }

        $this->assertSafeContent((string) $file['tmp_name'], $extension);

        $directory = str_replace('\\', '/', $directory);
        if (str_contains($directory, '..') || str_contains($directory, "\0")) {
            throw new UploadException('Path traversal detected in upload directory.');
        }

        $segments = array_values(array_filter(explode('/', trim($directory, '/')), fn ($s) => $s !== '' && $s !== '.'));
        foreach ($segments as $seg) {
            if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $seg)) {
                throw new UploadException('Invalid directory segment in upload path.');
            }
        }
        $cleanDir = implode('/', $segments);
        if ($cleanDir === '') {
            $cleanDir = 'general';
        }

        $filename = $this->generateFilename($original, $extension);
        $relative = $cleanDir . '/' . date('Y/m');
        $baseUploadPath = $this->getUploadBasePath();
        $uploadRoot = realpath($this->app->basePath($baseUploadPath));
        if ($uploadRoot === false) {
            $uploadRoot = $this->app->basePath($baseUploadPath);
            if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0775, true)) {
                throw new UploadException(trans('errors.upload_directory_failed'));
            }
            $uploadRoot = realpath($uploadRoot);
        }

        $targetDir = $this->app->basePath($baseUploadPath . '/' . $relative);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) {
            throw new UploadException(trans('errors.upload_directory_failed'));
        }

        $realTarget = realpath($targetDir);
        if ($realTarget === false || $uploadRoot === false || !str_starts_with($realTarget, $uploadRoot)) {
            throw new UploadException('Target upload directory is outside of allowed upload root.');
        }

        $moved = is_uploaded_file((string) $file['tmp_name'])
            ? move_uploaded_file((string) $file['tmp_name'], $targetDir . '/' . $filename)
            : (@rename((string) $file['tmp_name'], $targetDir . '/' . $filename) || @copy((string) $file['tmp_name'], $targetDir . '/' . $filename));

        if (!$moved) {
            throw new UploadException(trans('errors.upload_move_failed'));
        }

        $cleanBase = ltrim($baseUploadPath, '/');
        if (str_starts_with($cleanBase, 'public/')) {
            $webPrefix = '/' . substr($cleanBase, strlen('public/'));
        } else {
            $webPrefix = '/' . $cleanBase;
        }
        $webPath = rtrim($webPrefix, '/') . '/' . $relative . '/' . $filename;

        return [
            'path' => $webPath,
            'filename' => $filename,
            'original_name' => $original,
            'extension' => $extension,
            'mime' => (string) ($file['type'] ?? 'application/octet-stream'),
            'size' => (int) ($file['size'] ?? 0),
            'directory' => $cleanDir,
        ];
    }

    public function getUploadBasePath(?string $disk = null): string
    {
        $diskName = $disk ?? (string) config('uploads.disk', 'local');
        $disks = (array) config('uploads.disks', []);

        if (isset($disks[$diskName]['path']) && is_string($disks[$diskName]['path'])) {
            return $disks[$diskName]['path'];
        }

        if ($diskName === 'private' || $diskName === 'secure') {
            return 'storage/app/uploads';
        }

        return (string) config('uploads.path', 'public/images/uploads');
    }

    public function generateFilename(string $original, string $extension, ?string $strategy = null): string
    {
        $strategy = $strategy ?? (string) config('uploads.naming', 'random');

        if ($strategy === 'slug') {
            $baseName = pathinfo($original, PATHINFO_FILENAME);
            $slug = Str::slug($baseName);
            if ($slug === '') {
                $slug = 'file';
            }
            return $slug . '-' . Str::random(8) . '.' . $extension;
        }

        if ($strategy === 'original') {
            $baseName = pathinfo($original, PATHINFO_FILENAME);
            $cleanName = preg_replace('/[^a-zA-Z0-9_\-]+/', '-', $baseName);
            $cleanName = trim((string) $cleanName, '-');
            if ($cleanName === '') {
                $cleanName = 'file';
            }
            return $cleanName . '.' . $extension;
        }

        return Str::random(24) . '.' . $extension;
    }

    public function delete(string $relativePath, ?string $disk = null): bool
    {
        $clean = ltrim(str_replace('\\', '/', $relativePath), '/');
        if (str_contains($clean, '..') || str_contains($clean, "\0")) {
            return false;
        }

        $baseUploadPath = $this->getUploadBasePath($disk);
        $uploadRoot = realpath($this->app->basePath($baseUploadPath));
        if ($uploadRoot === false) {
            return false;
        }

        if (str_starts_with($clean, 'images/uploads/')) {
            $full = $this->app->basePath('public/' . $clean);
        } else {
            $full = $this->app->basePath($baseUploadPath . '/' . $clean);
        }

        $realTarget = realpath($full);
        if ($realTarget === false || !str_starts_with($realTarget, $uploadRoot) || !is_file($realTarget)) {
            return false;
        }

        return unlink($realTarget);
    }

    private function assertValidUpload(array $file): void
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new UploadException(trans('errors.upload_missing'));
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new UploadException(trans('errors.upload_php_error', ['code' => (string) $error]));
        }
    }

    private function assertAllowedExtension(string $extension): void
    {
        $blocked = (array) config('security.uploads.blocked_extensions', []);
        if (in_array($extension, $blocked, true)) {
            throw new UploadException(trans('errors.upload_type_blocked'));
        }

        $allowed = array_merge(
            (array) config('uploads.allowed_images', []),
            (array) config('uploads.allowed_documents', []),
            (array) config('uploads.allowed_videos', []),
        );

        if (!in_array($extension, $allowed, true)) {
            throw new UploadException(trans('errors.upload_type_not_allowed', ['extension' => $extension]));
        }
    }

    private function assertSafeContent(string $tmpPath, string $extension): void
    {
        if (!is_file($tmpPath) || !is_readable($tmpPath)) {
            throw new UploadException(trans('errors.upload_missing'));
        }

        $detectedMime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmpPath);
                if (is_string($mime)) {
                    $detectedMime = $mime;
                }
                finfo_close($finfo);
            }
        }

        $rasterMimes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
        ];
        if (isset($rasterMimes[$extension])) {
            if (!in_array($detectedMime, $rasterMimes[$extension], true)) {
                throw new UploadException(trans('errors.upload_type_not_allowed', ['extension' => $extension]));
            }
        }

        $sample = file_get_contents($tmpPath, false, null, 0, 8192);
        if ($sample !== false && (stripos($sample, '<?php') !== false || stripos($sample, '<?=') !== false)) {
            throw new UploadException(trans('errors.upload_type_blocked'));
        }

        if ($extension === 'svg' || str_contains($detectedMime, 'svg') || str_contains($detectedMime, 'xml')) {
            $content = file_get_contents($tmpPath);
            if ($content === false || trim($content) === '') {
                throw new UploadException('Uploaded SVG file is empty or unreadable.');
            }

            if (preg_match('/<\s*(?:script|foreignobject|iframe|embed|object|applet|meta|link|base)\b/i', $content)) {
                throw new UploadException('Uploaded SVG contains prohibited executable tags.');
            }

            if (preg_match('/\bon[a-z]{3,}\s*=/i', $content)) {
                throw new UploadException('Uploaded SVG contains prohibited event handlers.');
            }

            if (preg_match('/(?:href|xlink:href|src)\s*=\s*["\']?\s*(?:javascript|vbscript|data\s*:\s*text\/html)/i', $content)) {
                throw new UploadException('Uploaded SVG contains prohibited script or data URIs.');
            }

            if (preg_match('/<!(?:ENTITY|DOCTYPE\s+[^>]*SYSTEM)/i', $content)) {
                throw new UploadException('Uploaded SVG contains prohibited external entity references.');
            }

            if (preg_match('/<\?xml-stylesheet/i', $content)) {
                throw new UploadException('Uploaded SVG contains prohibited XML stylesheets.');
            }
        }
    }
}
