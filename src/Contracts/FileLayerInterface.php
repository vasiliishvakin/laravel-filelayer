<?php

namespace Vaskiq\LaravelFileLayer\Contracts;

use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;

interface FileLayerInterface
{
    public const ETAG_HASH_ALGORITHM = 'md5';

    public const HASH_ALGORITHM = 'sha1';

    public function storageByFile(FileSystemItemWrapperInterface $file): StorageWrapper;

    public function path(FileSystemItemWrapperInterface $file): string;

    public function isLocal(FileSystemItemWrapperInterface $file): bool;

    public function fullPath(FileSystemItemWrapperInterface $file): string;
}
