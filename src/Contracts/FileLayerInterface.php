<?php

namespace Vaskiq\LaravelFileLayer\Contracts;

use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;

interface FileLayerInterface
{
    public function storageByFile(FileSystemItemWrapperInterface $file): StorageWrapper;

    public function path(FileSystemItemWrapperInterface $file): string;

    public function isLocal(FileSystemItemWrapperInterface $file): bool;

    public function fullPath(FileSystemItemWrapperInterface $file): string;
}
