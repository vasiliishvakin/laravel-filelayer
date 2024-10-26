<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Facades;

use Illuminate\Support\Facades\Facade;
use Vaskiq\LaravelFileLayer\FileLayer;
use Vaskiq\LaravelFileLayer\TmpFileLayer as TmpFileLayerClass;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

/**
 * @method static TmpFileWrapper create(?string $content = null, ?string $mime = null, ?FileLayer $manager = null)
 */
class TmpFileLayer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TmpFileLayerClass::class;
    }
}
