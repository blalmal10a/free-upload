<?php

namespace Blalmal10a\FreeUpload\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class FreeUploadUploadController
{
    public function __invoke(Request $request): JsonResponse
    {
        $maxSizeKb = (int) config('free-upload.max_size_kb', 32768);

        $request->validate([
            'file' => ['required', 'file', "max:{$maxSizeKb}"],
            'filename' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'encoded' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');

        $id = $this->forwardToHost($file);

        if ($id === null) {
            return response()->json(['message' => 'The upstream upload failed.'], 502);
        }

        $filename = $request->input('filename', $file->getClientOriginalName());
        $filename = (string) str($filename)->replace('\\', '/')->basename();

        $base = rtrim((string) config('free-upload.decode_base_url'), '/');
        $url = $request->boolean('encoded')
            ? "{$base}/dec/{$id}/{$filename}"
            : "{$base}/{$id}/{$filename}";

        return response()->json(['url' => $url]);
    }

    /**
     * @return string|null the hosted image id (basename of the hosted URL), or null on failure
     */
    private function forwardToHost(UploadedFile $file): ?string
    {
        $host = (string) config('free-upload.upload_host');
        $key = (string) config('free-upload.api_key');

        $payload = array_filter([
            'key' => $key,
            'mime_type' => $file->getMimeType(),
        ]);

        try {
            $response = Http::asMultipart()
                ->timeout(120)
                ->attach('source', $file->get(), $file->getClientOriginalName())
                ->post($host, $payload);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        $url = data_get($data, 'image.image.url')
            ?? data_get($data, 'image.url')
            ?? data_get($data, 'url');

        if (! is_string($url)) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $id = basename($path);

        return $id !== '' && $id !== '.' ? $id : null;
    }
}
