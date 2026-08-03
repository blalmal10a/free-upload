<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Proxy;

use RuntimeException;

class PxvtDecodeException extends RuntimeException
{
    public function __construct(string $message, public readonly int $httpCode = 500)
    {
        parent::__construct($message);
    }
}
