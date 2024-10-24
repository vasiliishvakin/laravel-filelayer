<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Facades;

use Illuminate\Support\Facades\Facade;
use Vaskiq\LaravelFileLayer\Helpers\MimeHelper;

/**
 * @method static getExtensions(string $mimeType): array
 * @method static function getMimeTypes(string $ext): array
 * @method static function extension(string $mimeType): string
 */
class Mime extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MimeHelper::class;
    }
}
