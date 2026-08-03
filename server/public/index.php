<?php

declare(strict_types=1);

use Blalmal10a\FreeUpload\Proxy\Proxy;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($autoload)) {
    require $autoload;
} else {
    require dirname(__DIR__, 2) . '/src/Proxy/PxvtDecodeException.php';
    require dirname(__DIR__, 2) . '/src/Proxy/PxvtDecoder.php';
    require dirname(__DIR__, 2) . '/src/Proxy/ProxyResponse.php';
    require dirname(__DIR__, 2) . '/src/Proxy/Proxy.php';
}

$imageHost = getenv('FREEUPLOAD_IMAGE_HOST') ?: 'https://iili.io';
$timeout = (int) (getenv('FREEUPLOAD_PROXY_TIMEOUT') ?: 10);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$response = (new Proxy($imageHost, $timeout))->handle($method, $path);

http_response_code($response->status);

foreach ($response->headers as $name => $value) {
    header("{$name}: {$value}");
}

echo $response->body;
