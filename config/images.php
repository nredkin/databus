<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Fetch Settings
    |--------------------------------------------------------------------------
    |
    | Limits applied when the application downloads an image from a remote
    | URL on behalf of an API client.
    |
    */

    // URL schemes the fetcher is allowed to use.
    'allowed_schemes' => ['http', 'https'],

    // Maximum accepted image size in bytes (default 10 MB).
    'max_bytes' => (int) env('IMAGES_MAX_BYTES', 10 * 1024 * 1024),

    // Maximum time in seconds allowed to establish a connection.
    'connect_timeout' => (int) env('IMAGES_CONNECT_TIMEOUT', 5),

    // Maximum total time in seconds allowed for the whole request.
    'timeout' => (int) env('IMAGES_TIMEOUT', 15),

    // Maximum number of redirects followed while fetching.
    'max_redirects' => (int) env('IMAGES_MAX_REDIRECTS', 3),

    // Upstream Content-Type must start with this prefix.
    'content_type_prefix' => 'image/',

];
