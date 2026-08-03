<?php

namespace Blalmal10a\FreeUpload\Proxy;

use CurlHandle;

final class Proxy
{
    private const CORS_HEADERS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
    ];

    private const MIME_TYPES = [
        'avif' => 'image/avif',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
    ];

    private readonly string $imageHost;

    /**
     * @var (callable(string): array{status: int, body: string})|null
     */
    private $customFetcher;

    /**
     * @param  callable(string): array{status: int, body: string}|null  $fetcher
     */
    public function __construct(string $imageHost, private readonly int $timeoutSeconds = 10, ?callable $fetcher = null)
    {
        $this->imageHost = rtrim($imageHost, '/');
        $this->customFetcher = $fetcher;
    }

    public function handle(string $method, string $path): ProxyResponse
    {
        if ($method === 'OPTIONS') {
            return $this->response(204);
        }

        if (preg_match('#^/dec/([^/]+)/([^/]+)$#', $path, $matches) === 1) {
            return $this->decode($matches[1]);
        }

        if (preg_match('#^/([^/]+)(?:/([^/]+))?$#', $path, $matches) === 1) {
            return $this->proxy($matches[1]);
        }

        return $this->response(400, 'Bad request.');
    }

    private function proxy(string $id): ProxyResponse
    {
        $upstream = $this->fetch($this->imageHost . '/' . rawurlencode($id));

        if ($upstream['status'] >= 400) {
            return $this->response(404, 'Not Found.');
        }

        return $this->response(200, $upstream['body'], [
            'Cache-Control' => 'public, max-age=86400',
            'Content-Type' => $this->detectContentType($id),
        ]);
    }

    private function decode(string $id): ProxyResponse
    {
        $upstream = $this->fetch($this->imageHost . '/' . rawurlencode($id));

        if ($upstream['status'] >= 400) {
            return $this->response(502, 'Upstream unavailable.');
        }

        try {
            $decoded = (new PxvtDecoder)->decode($upstream['body']);
        } catch (PxvtDecodeException $exception) {
            return $this->response($exception->httpCode, $exception->getMessage());
        }

        return $this->response(200, $decoded['bytes'], [
            'Cache-Control' => 'no-store',
            'Content-Disposition' => $this->contentDisposition($decoded['filename']),
            'Content-Type' => $decoded['mimeType'],
        ]);
    }

    private function detectContentType(string $id): string
    {
        $extension = strtolower(pathinfo($id, PATHINFO_EXTENSION));

        return self::MIME_TYPES[$extension] ?? 'application/octet-stream';
    }

    private function contentDisposition(string $filename): string
    {
        $filename = str_replace(['"', "\r", "\n"], '', $filename);

        if ($filename === '') {
            return 'inline';
        }

        return "inline; filename*=UTF-8''" . rawurlencode($filename);
    }

    /**
     * @return array{status: int, body: string}
     */
    private function fetch(string $url): array
    {
        /** @var callable(string): array{status: int, body: string} $fetcher */
        $fetcher = $this->customFetcher ?? $this->defaultFetcher();

        return $fetcher($url);
    }

    /**
     * @return callable(string): array{status: int, body: string}
     */
    private function defaultFetcher(): callable
    {
        return function (string $url): array {
            $curl = curl_init($url);

            if (! $curl instanceof CurlHandle) {
                return ['status' => 502, 'body' => ''];
            }

            curl_setopt_array($curl, [
                CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
                CURLOPT_USERAGENT => 'free-upload-proxy/1.0',
            ]);

            $body = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

            curl_close($curl);

            return ['status' => $status, 'body' => is_string($body) ? $body : ''];
        };
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function response(int $status, string $body = '', array $headers = []): ProxyResponse
    {
        $headers['Content-Type'] ??= 'text/plain; charset=UTF-8';

        if ($status !== 200) {
            $headers['Cache-Control'] ??= 'no-store';
        }

        return new ProxyResponse($status, array_merge(self::CORS_HEADERS, $headers), $body);
    }
}
