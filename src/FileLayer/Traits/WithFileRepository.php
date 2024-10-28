<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer\Traits;

use Vaskiq\LaravelFileLayer\Repositories\FileRepository;

trait WithFileRepository
{
    abstract public function fileRepository(): FileRepository;

    public function registeredByPath(string $path): bool
    {
        return $this->fileRepository()->existsByPath($path);
    }
}
