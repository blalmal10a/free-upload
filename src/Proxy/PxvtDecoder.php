<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Proxy;

final class PxvtDecoder
{
    /**
     * Decodes a PNG whose pixels carry a PXVT payload.
     *
     * Byte layout: 0-3 magic "PXVT" | 4-5 filename length (BE u16) | 6-7 MIME
     * length (BE u16) | 8..8+n UTF-8 filename | 8+n..h MIME | h.. file bytes
     * zero-padded to a multiple of 3.
     *
     * @return array{filename: string, mimeType: string, bytes: string}
     *
     * @throws PxvtDecodeException
     */
    public function decode(string $pngBytes): array
    {
        $image = @imagecreatefromstring($pngBytes);

        if ($image === false) {
            throw new PxvtDecodeException('Invalid or truncated image payload.', 400);
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);

            $payload = '';

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $rgb = imagecolorat($image, $x, $y);
                    $payload .= chr(($rgb >> 16) & 0xFF) . chr(($rgb >> 8) & 0xFF) . chr($rgb & 0xFF);
                }
            }

            if (strlen($payload) < 8 || ! str_starts_with($payload, 'PXVT')) {
                throw new PxvtDecodeException('Invalid vault signature format.', 400);
            }

            $filenameLength = $this->readUInt16BigEndian($payload, 4);
            $mimeTypeLength = $this->readUInt16BigEndian($payload, 6);

            $offset = 8;

            if ($offset + $filenameLength + $mimeTypeLength > strlen($payload)) {
                throw new PxvtDecodeException('Truncated vault payload.', 400);
            }

            $filename = substr($payload, $offset, $filenameLength);
            $offset += $filenameLength;
            $mimeType = substr($payload, $offset, $mimeTypeLength);
            $offset += $mimeTypeLength;

            $bytes = rtrim(substr($payload, $offset), "\0");

            return [
                'filename' => $filename,
                'mimeType' => $mimeType,
                'bytes' => $bytes,
            ];
        } finally {
            imagedestroy($image);
        }
    }

    private function readUInt16BigEndian(string $payload, int $offset): int
    {
        return (ord($payload[$offset]) << 8) | ord($payload[$offset + 1]);
    }
}
