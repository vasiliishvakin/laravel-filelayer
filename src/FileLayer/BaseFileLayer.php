<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer;

use Vaskiq\LaravelFileLayer\Contracts\FileLayerInterface;
use Vaskiq\LaravelFileLayer\Contracts\FileSystemItemWrapperInterface;
use Vaskiq\LaravelFileLayer\Storage\StorageOperator;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;

/**
 * @template TFileWrapper of FileSystemItemWrapperInterface
 */
class BaseFileLayer implements FileLayerInterface
{
    public function __construct(
        private readonly StorageOperator $storageOperator,
    ) {}

    public function storageOperator(): StorageOperator
    {
        return $this->storageOperator;
    }

    public function storageByFile(FileSystemItemWrapperInterface $file): StorageWrapper
    {
        return $this->storageOperator()->storage($file->storage());
    }

    public function path(FileSystemItemWrapperInterface $file): string
    {
        return $file->data()->path;
    }

    public function fullPath(FileSystemItemWrapperInterface $file): string
    {
        return $this->storageByFile($file)->path($this->path($file));
    }

    public function isLocal(FileSystemItemWrapperInterface $file): bool
    {
        return $this->storageOperator()->isLocal($this->storageByFile($file));
    }

    public function exists(FileSystemItemWrapperInterface $file): bool
    {
        return $this->storageByFile($file)->exists($this->path($file));
    }
}
