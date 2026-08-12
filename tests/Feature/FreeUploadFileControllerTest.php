<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Feature;

use Blalmal10a\FreeUpload\Proxy\Proxy;
use Illuminate\Foundation\Auth\User;

beforeEach(function (): void {
    $this->actingAs(new class extends User
    {
        public $timestamps = false;
    });
});

it('streams raw images from the image path', function (): void {
    $this->app->instance(Proxy::class, new Proxy(
        'https://iili.io',
        fetcher: fn (string $url): array => ['status' => 200, 'body' => 'png-bytes'],
    ));

    $response = $this->get('/freeupload/images/abc123.png/photo.png');

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertContent('png-bytes');
});

it('decodes and streams files from the files path', function (): void {
    $bytes = random_bytes(16);
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

    $this->app->instance(Proxy::class, new Proxy(
        'https://iili.io',
        fetcher: fn (): array => ['status' => 200, 'body' => $png],
    ));

    $response = $this->get('/freeupload/files/abc123.png/report.pdf');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertContent($bytes);
});
