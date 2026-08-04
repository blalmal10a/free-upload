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

        $base = (string) config('free-upload.proxy_base_url');

        if ($base === '') {
            $prefix = (string) config('free-upload.route_prefix', 'freeupload');
            $base = rtrim(url("/{$prefix}"), '/');
        } else {
            $base = rtrim($base, '/');
        }

        $encoded = $request->boolean('encoded');

        $path = $encoded
            ? (string) config('free-upload.files_path', 'files')
            : (string) config('free-upload.image_path', 'images');

        $id = $this->withExtension($id, $encoded ? 'png' : $file->getClientOriginalExtension());

        $url = "{$base}/{$path}/{$id}/{$filename}";

        return response()->json(['url' => $url]);
    }

    private function withExtension(string $id, string $extension): string
    {
        if ($extension === '' || pathinfo($id, PATHINFO_EXTENSION) !== '') {
            return $id;
        }

        return "{$id}.{$extension}";
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
