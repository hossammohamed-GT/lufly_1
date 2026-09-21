<?php

declare(strict_types=1);

return [
    'disk' => env('UPLOAD_DISK', 'local'),

    // Stored inside public/images/uploads (accessible directly and via asset()).
    'path' => 'public/images/uploads',

    'max_size_kb' => (int) env('UPLOAD_MAX_SIZE', 10240),

    'allowed_images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    'allowed_documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
    'allowed_videos' => ['mp4', 'webm', 'mov'],

    'naming' => 'random', // random | slug
];
