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

    /**
     * Validate + store an uploaded file.
     *
     * @param array<string, mixed> $file $_FILES entry
     * @return array{path: string, filename: string, original_name: string, extension: string, mime: string, size: int, directory: string}
     */
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

        $directory = str_replace('\\', '/', $directory);
        if (str_contains($directory, '..') || str_contains($directory, "\0")) {
            throw new UploadException('Path traversal detected in upload directory.');
        }

        // Sanitize path segments: only allow letters, numbers, hyphens, and underscores
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

        $filename = Str::random(24) . '.' . $extension;
        $relative = $cleanDir . '/' . date('Y/m');
        $baseUploadPath = (string) config('uploads.path', 'public/images/uploads');
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

        // Verify targetDir is strictly within uploadRoot
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

        // Generate web-accessible path (compatible with asset() and storefront)
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

    public function delete(string $relativePath): bool
    {
        $clean = ltrim(str_replace('\\', '/', $relativePath), '/');
        if (str_contains($clean, '..') || str_contains($clean, "\0")) {
            return false;
        }

        $baseUploadPath = (string) config('uploads.path', 'public/images/uploads');
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

    /** @param array<string, mixed> $file */
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
}
