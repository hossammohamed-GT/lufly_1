<?php

declare(strict_types=1);

return [
    'disk' => env('UPLOAD_DISK', 'local'),

    // Relative to storage/uploads (served through /storage/uploads/...).
    'path' => 'storage/uploads',

    'max_size_kb' => (int) env('UPLOAD_MAX_SIZE', 10240),

    /* SVG removed (2026-09-20 audit): an uploaded SVG served from this same
       origin would execute embedded <script> => stored XSS. Raster only. */
    'allowed_images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'allowed_documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
    'allowed_videos' => ['mp4', 'webm', 'mov'],

    'naming' => 'random', // random | slug
];
