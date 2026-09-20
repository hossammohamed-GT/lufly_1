<?php

declare(strict_types=1);

return [
    'server_error' => 'Something went wrong. Please try again later.',
    'page_not_found_title' => 'Page not found',
    'page_not_found_message' => 'The page you are looking for may have been moved, renamed, or never existed. Let us guide you back.',
    'page_not_found_hint' => 'or use the menu above to keep exploring',
    'back_home' => 'Back to homepage',
    'browse_products' => 'Browse the catalog',
    'product_not_found' => 'The requested product could not be found.',
    'user_not_found' => 'The requested user could not be found.',
    'media_not_found' => 'The requested media item could not be found.',

    'upload_missing' => 'No file was uploaded.',
    'upload_too_large' => 'The file exceeds the maximum size of :max KB.',
    'upload_directory_failed' => 'The upload directory could not be created.',
    'upload_move_failed' => 'The uploaded file could not be stored.',
    'upload_type_not_allowed' => 'Files with extension ":extension" are not allowed.',
    'upload_type_blocked' => 'This file type is blocked for security reasons.',
    'upload_php_error' => 'Upload failed with PHP error code :code.',

    'validation' => [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'string' => 'The :attribute must be text.',
        'numeric' => 'The :attribute must be a number.',
        'integer' => 'The :attribute must be an integer.',
        'boolean' => 'The :attribute must be true or false.',
        'min' => 'The :attribute must be at least :param.',
        'max' => 'The :attribute may not be greater than :param.',
        'in' => 'The selected :attribute is invalid.',
        'url' => 'The :attribute must be a valid URL.',
        'date' => 'The :attribute must be a valid date.',
        'array' => 'The :attribute must be a list.',
        'confirmed' => 'The :attribute confirmation does not match.',
        'unique' => 'The :attribute has already been taken.',
        'image' => 'The :attribute must be an image.',
        'file' => 'The :attribute must be an uploaded file.',
        'mimes' => 'The :attribute must be a file of type: :param.',
    ],

    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'price' => 'price',
        'sku' => 'SKU',
        'status' => 'status',
        'file' => 'file',
        'code' => 'code',
        'title' => 'title',
    ],
    'not_found' => 'The requested record could not be found.',
];
