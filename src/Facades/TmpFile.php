<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Facades;

use Illuminate\Support\Facades\Facade;
use Vaskiq\LaravelFileLayer\TmpFileLayer;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

/**
 * @method static TmpFileWrapper create(?string $content = null, ?string $mime = null, bool $lazyDelete = false)
 */
class TmpFile extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TmpFileLayer::class;
    }
}
