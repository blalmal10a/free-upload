<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload endpoint
    |--------------------------------------------------------------------------
    |
    | When set, this overrides the plugin's own upload route. Leave null to
    | use the built-in 'freeupload.upload' route.
    |
    */

    'upload_endpoint' => env('FREEUPLOAD_UPLOAD_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | Decode base URL
    |--------------------------------------------------------------------------
    |
    | Base URL of the proxy server used to serve stored files. Decoded file
    | URLs are built as "{url}/dec/{id}/{filename}".
    |
    */

    'decode_base_url' => env('FREEUPLOAD_DECODE_BASE_URL', 'https://media-server.kawnek.workers.dev'),

    /*
    |--------------------------------------------------------------------------
    | Upstream hosting API
    |--------------------------------------------------------------------------
    |
    | The upload controller forwards files to this host and stores the hosted
    | image id. Empty api_key is omitted from the request.
    |
    */

    'upload_host' => env('FREEUPLOAD_UPLOAD_HOST', 'https://freeimage.host/api/1/upload'),
    'api_key' => env('FREEUPLOAD_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    |
    | max_size_kb validates the file server-side. max_encoded_file_mb caps the
    | original file size of non-image files that get PXVT-encoded client side.
    |
    */

    'max_size_kb' => (int) env('FREEUPLOAD_MAX_SIZE_KB', 32768),
    'max_encoded_file_mb' => (int) env('FREEUPLOAD_MAX_ENCODED_FILE_MB', 30),

    /*
    |--------------------------------------------------------------------------
    | Image host (proxy upstream)
    |--------------------------------------------------------------------------
    |
    | Host the proxy fetches files from, used by the standalone server.
    |
    */

    'image_host' => env('FREEUPLOAD_IMAGE_HOST', 'https://iili.io'),

    /*
    |--------------------------------------------------------------------------
    | Plugin routes
    |--------------------------------------------------------------------------
    */

    'register_routes' => env('FREEUPLOAD_REGISTER_ROUTES', true),
    'route_prefix' => env('FREEUPLOAD_ROUTE_PREFIX', 'freeupload'),
    'route_middleware' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FREEUPLOAD_ROUTE_MIDDLEWARE', 'web,auth'))
    ))),
];
