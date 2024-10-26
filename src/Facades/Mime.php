<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Facades;

use Illuminate\Support\Facades\Facade;
use Vaskiq\LaravelFileLayer\Helpers\MimeHelper;

/**
 * @method static array getExtensions(string $mimeType)
 * @method static array getMimeTypes(string $ext)
 * @method static string extension(string $mimeType)
 */
class Mime extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MimeHelper::class;
    }
}
