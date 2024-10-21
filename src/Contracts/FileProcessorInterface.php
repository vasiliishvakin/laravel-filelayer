<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Contracts;

use Closure;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

interface FileProcessorInterface
{
    public function handle(FileWrapper $file, Closure $next);
}
