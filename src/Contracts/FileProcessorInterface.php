<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Contracts;

use Closure;

interface FileProcessorInterface
{
    public function handle(FsInfoInterface $file, Closure $next): FsInfoInterface;
}
