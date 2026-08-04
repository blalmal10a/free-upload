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
    | Proxy base URL
    |--------------------------------------------------------------------------
    |
    | Base URL of the proxy that serves stored files. When null, the app's own
    | routes are used (the plugin registers /{image_path} and /{files_path}
    | routes under the route prefix). Stored file URLs are built as
    | "{base}/{path}/{id}/{filename}".
    |
    */

    'proxy_base_url' => env('FREEUPLOAD_PROXY_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | File paths
    |--------------------------------------------------------------------------
    |
    | Path segments used to serve stored files. Files under the image path are
    | streamed as-is; files under the files path are PXVT-decoded first.
    |
    */

    'image_path' => env('FREEUPLOAD_IMAGE_PATH', 'images'),
    'files_path' => env('FREEUPLOAD_FILES_PATH', 'files'),

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
    'api_key' => env('FREEUPLOAD_API_KEY', '6d207e02198a847aa98d0a2a901485a5'),

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
    | Host the proxy fetches files from. proxy_timeout caps each upstream fetch.
    |
    */

    'image_host' => env('FREEUPLOAD_IMAGE_HOST', 'https://iili.io'),
    'proxy_timeout' => (int) env('FREEUPLOAD_PROXY_TIMEOUT', 10),

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
