<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Unit;

use Blalmal10a\FreeUpload\Proxy\Proxy;

it('answers OPTIONS requests with 204 and CORS headers', function (): void {
    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 200, 'body' => '']);

    $response = $proxy->handle('OPTIONS', '/anything');

    expect($response->status)->toBe(204)
        ->and($response->headers['Access-Control-Allow-Origin'])->toBe('*')
        ->and($response->headers['Access-Control-Allow-Methods'])->toBe('GET, OPTIONS');
});

it('proxies image requests with a long cache lifetime', function (): void {
    $fetched = [];

    $proxy = new Proxy('https://iili.io', fetcher: function (string $url) use (&$fetched): array {
        $fetched[] = $url;

        return ['status' => 200, 'body' => 'png-bytes'];
    });

    $response = $proxy->handle('GET', '/abc123.png');

    expect($response->status)->toBe(200)
        ->and($response->body)->toBe('png-bytes')
        ->and($response->headers['Cache-Control'])->toBe('public, max-age=86400')
        ->and($response->headers['Content-Type'])->toBe('image/png')
        ->and($fetched)->toBe(['https://iili.io/abc123.png']);
});

it('returns 404 when the upstream image is missing', function (): void {
    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 404, 'body' => '']);

    expect($proxy->handle('GET', '/missing.png')->status)->toBe(404);
});

it('falls back to octet-stream for unknown extensions', function (): void {
    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 200, 'body' => '']);

    $response = $proxy->handle('GET', '/abc123');

    expect($response->headers['Content-Type'])->toBe('application/octet-stream');
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

    $response = $proxy->handle('GET', '/dec/abc123/report.pdf');

    expect($response->status)->toBe(200)
        ->and($response->body)->toBe($bytes)
        ->and($response->headers['Content-Type'])->toBe('application/pdf')
        ->and($response->headers['Content-Disposition'])->toBe("inline; filename*=UTF-8''report.pdf")
        ->and($response->headers['Cache-Control'])->toBe('no-store');
});

it('returns 502 when the upstream fails for a decode request', function (): void {
    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 500, 'body' => '']);

    expect($proxy->handle('GET', '/dec/abc123/file.pdf')->status)->toBe(502);
});

it('returns 400 for invalid vault payloads', function (): void {
    $image = imagecreatetruecolor(4, 4);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

    ob_start();
    imagepng($image);
    $png = ob_get_clean();

    imagedestroy($image);

    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 200, 'body' => $png]);

    expect($proxy->handle('GET', '/dec/abc123/file.pdf')->status)->toBe(400);
});

it('returns 400 for unknown routes', function (): void {
    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 200, 'body' => '']);

    expect($proxy->handle('GET', '/a/b/c')->status)->toBe(400);
});

it('merges CORS headers onto every response', function (): void {
    $proxy = new Proxy('https://iili.io', fetcher: fn (): array => ['status' => 404, 'body' => '']);

    $response = $proxy->handle('GET', '/missing.png');

    expect($response->headers['Access-Control-Allow-Origin'])->toBe('*');
});
