<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Contracts;

use Closure;
use Vaskiq\LaravelFileLayer\Wrappers\BaseFileWrapper;

interface FileProcessorInterface
{
    public function handle(BaseFileWrapper $file, Closure $next): BaseFileWrapper;
}
