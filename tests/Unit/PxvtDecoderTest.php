<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Unit;

use Blalmal10a\FreeUpload\Proxy\PxvtDecodeException;
use Blalmal10a\FreeUpload\Proxy\PxvtDecoder;

beforeEach(function (): void {
    $this->decoder = new PxvtDecoder;
});

function encodePxvtPayload(string $payload): string
{
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

    return $png;
}

function buildPxvtPayload(string $filename, string $mimeType, string $bytes): string
{
    $filenameBytes = $filename;
    $mimeTypeBytes = $mimeType;

    return 'PXVT'
        . chr(strlen($filenameBytes) >> 8) . chr(strlen($filenameBytes) & 0xFF)
        . chr(strlen($mimeTypeBytes) >> 8) . chr(strlen($mimeTypeBytes) & 0xFF)
        . $filenameBytes
        . $mimeTypeBytes
        . $bytes;
}

it('decodes a round-tripped payload', function (): void {
    $bytes = "binary\x00content\xFF\xFE" . random_bytes(64);

    $png = encodePxvtPayload(buildPxvtPayload('report.pdf', 'application/pdf', $bytes));

    expect($this->decoder->decode($png))->toBe([
        'filename' => 'report.pdf',
        'mimeType' => 'application/pdf',
        'bytes' => $bytes,
    ]);
});

it('handles UTF-8 filenames', function (): void {
    $png = encodePxvtPayload(buildPxvtPayload('résumé-événement.pdf', 'application/pdf', 'data'));

    $decoded = $this->decoder->decode($png);

    expect($decoded['filename'])->toBe('résumé-événement.pdf');
});

it('trims zero padding from the tail of the payload', function (): void {
    $bytes = 'data';

    $png = encodePxvtPayload(buildPxvtPayload('file.bin', 'application/octet-stream', $bytes));

    expect($this->decoder->decode($png)['bytes'])->toBe($bytes);
});

it('rejects invalid PNG payloads', function (): void {
    $this->decoder->decode('not an image');
})->throws(PxvtDecodeException::class, 'Invalid or truncated image payload.');

it('rejects payloads without the PXVT magic', function (): void {
    $payload = buildPxvtPayload('file.bin', 'application/octet-stream', 'data');

    $payload = 'XXXX' . substr($payload, 4);

    $png = encodePxvtPayload($payload);

    $this->decoder->decode($png);
})->throws(PxvtDecodeException::class, 'Invalid vault signature format.');

it('rejects truncated payloads', function (): void {
    $payload = 'PXVT'
        . chr(0) . chr(250)
        . chr(0) . chr(10)
        . 'short';

    $png = encodePxvtPayload($payload);

    $this->decoder->decode($png);
})->throws(PxvtDecodeException::class, 'Truncated vault payload.');

it('exposes the http code on decode exceptions', function (): void {
    try {
        $this->decoder->decode('not an image');

        $this->fail('Expected PxvtDecodeException');
    } catch (PxvtDecodeException $exception) {
        expect($exception->httpCode)->toBe(400);
    }
});
