<?php

namespace Blalmal10a\FreeUpload\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Blalmal10a\FreeUpload\FreeUpload
 */
class FreeUpload extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Blalmal10a\FreeUpload\FreeUpload::class;
    }
}
