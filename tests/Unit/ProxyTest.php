<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Unit;

use Blalmal10a\FreeUpload\Proxy\Proxy;

it('answers OPTIONS requests with 204 and CORS headers', function (): void {
    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 200, 'body' => '']);

    $response = $proxy->handle('OPTIONS', '/anything');

    expect($response->status)->toBe(204)
        ->and($response->headers['Access-Control-Allow-Origin'])->toBe('*');
});

it('proxies image requests with a long cache lifetime', function (): void {
    $fetched = [];

    $proxy = new Proxy('https://iili.io', fetcher: function (string $url) use (&$fetched): array {
        $fetched[] = $url;

        return ['status' => 200, 'body' => 'png-bytes'];
    });

    $response = $proxy->handle('GET', '/images/abc123.png');

    expect($response->status)->toBe(200)
        ->and($response->body)->toBe('png-bytes')
        ->and($response->headers['Cache-Control'])->toBe('public, max-age=86400')
        ->and($fetched)->toBe(['https://iili.io/abc123.png']);
});

it('returns 404 when the upstream image is missing', function (): void {
    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 404, 'body' => '']);

    expect($proxy->handle('GET', '/images/missing.png')->status)->toBe(404);
});

it('decodes vault payloads and returns the original file', function (): void {
    $bytes = random_bytes(32);
    $filename = 'report.pdf';

    $payload = 'PXVT'
        . chr(strlen($filename) >> 8) . chr(strlen($filename) & 0xFF)
        . chr(0) . chr(15)
        . $filename
        . 'application/pdf'
        . $bytes;

    $paddedLength = (int) ceil(strlen($payload) / 3) * 3;
    $pixelCount = (int) ceil($paddedLength / 3);
    $side = (int) ceil(sqrt($pixelCount));

    $image = imagecreatetruecolor($side, $side);

    for ($i = 0; $i < $pixelCount; $i++) {
        $offset = $i * 3;

        $color = imagecolorallocate(
            $image,
            ord($payload[$offset]),
            ord($payload[$offset + 1] ?? "\0"),
            ord($payload[$offset + 2] ?? "\0"),
        );

        imagesetpixel($image, $i % $side, (int) floor($i / $side), $color);
    }

    ob_start();
    imagepng($image);
    $png = ob_get_clean();

    imagedestroy($image);

    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 200, 'body' => $png]);

    $response = $proxy->handle('GET', '/files/abc123/report.pdf');

    expect($response->status)->toBe(200)
        ->and($response->body)->toBe($bytes)
        ->and($response->headers['Content-Type'])->toBe('application/pdf')
        ->and($response->headers['Cache-Control'])->toBe('no-store');
});
