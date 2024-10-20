<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Facades;

use Illuminate\Support\Facades\Facade;
use Vaskiq\LaravelFileLayer\StorageManager;
use Vaskiq\LaravelFileLayer\TmpFilesManager;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

/**
 * @method static TmpFileWrapper create(?string $content = null, ?string $mime = null, ?StorageManager $manager = null)
 */
class TmpFile extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TmpFilesManager::class;
    }
}
