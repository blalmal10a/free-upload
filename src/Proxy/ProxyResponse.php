<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Proxy;

final readonly class ProxyResponse
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public int $status,
        public array $headers = [],
        public string $body = '',
    ) {}
}
