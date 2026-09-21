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

        $filename = Str::random(24) . '.' . $extension;
        $relative = trim($directory, '/') . '/' . date('Y/m');
        $baseUploadPath = (string) config('uploads.path', 'public/images/uploads');
        $targetDir = $this->app->basePath($baseUploadPath . '/' . $relative);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) {
            throw new UploadException(trans('errors.upload_directory_failed'));
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
            'directory' => trim($directory, '/'),
        ];
    }

    public function delete(string $relativePath): bool
    {
        $clean = ltrim($relativePath, '/');
        $baseUploadPath = (string) config('uploads.path', 'public/images/uploads');
        
        if (str_starts_with($clean, 'images/uploads/')) {
            $full = $this->app->basePath('public/' . $clean);
        } else {
            $full = $this->app->basePath($baseUploadPath . '/' . $clean);
        }

        return is_file($full) ? unlink($full) : false;
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
