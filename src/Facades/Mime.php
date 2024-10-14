<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Facades;

use Illuminate\Support\Facades\Facade;
use Vaskiq\LaravelFileLayer\Helpers\MimeHelper;

class Mime extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MimeHelper::class;
    }
}
