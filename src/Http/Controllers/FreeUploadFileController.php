<?php

namespace Blalmal10a\FreeUpload\Http\Controllers;

use Blalmal10a\FreeUpload\Proxy\Proxy;
use Blalmal10a\FreeUpload\Proxy\ProxyResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FreeUploadFileController
{
    public function __construct(private readonly ?Proxy $proxy = null) {}

    public function image(Request $request, string $id, string $filename): Response
    {
        return $this->serve((string) config('free-upload.image_path', 'images'), $id, $filename);
    }

    public function file(Request $request, string $id, string $filename): Response
    {
        return $this->serve((string) config('free-upload.files_path', 'files'), $id, $filename);
    }

    private function serve(string $path, string $id, string $filename): Response
    {
        $proxy = $this->proxy ?? new Proxy(
            (string) config('free-upload.image_host', 'https://iili.io'),
            (int) config('free-upload.proxy_timeout', 10)
        );

        $response = $proxy->handle('GET', "/{$path}/{$id}/{$filename}");

        return $this->toLaravelResponse($response);
    }

    private function toLaravelResponse(ProxyResponse $proxyResponse): Response
    {
        return response($proxyResponse->body, $proxyResponse->status, $proxyResponse->headers);
    }
}
