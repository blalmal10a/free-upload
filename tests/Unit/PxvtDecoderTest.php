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
    return 'PXVT'
        . chr(strlen($filename) >> 8) . chr(strlen($filename) & 0xFF)
        . chr(strlen($mimeType) >> 8) . chr(strlen($mimeType) & 0xFF)
        . $filename
        . $mimeType
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

it('rejects invalid PNG payloads', function (): void {
    $this->decoder->decode('not an image');
})->throws(PxvtDecodeException::class, 'Invalid or truncated image payload.');

it('rejects payloads without the PXVT magic', function (): void {
    $payload = buildPxvtPayload('file.bin', 'application/octet-stream', 'data');
    $payload = 'XXXX' . substr($payload, 4);

    $this->decoder->decode(encodePxvtPayload($payload));
})->throws(PxvtDecodeException::class, 'Invalid vault signature format.');
