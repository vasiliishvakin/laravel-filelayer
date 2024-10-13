<?php

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\StorageManager;

/** @mixin Filesystem */
class FileWrapper
{
    public function __construct(
        protected readonly StorageManager $storageManager,
        protected FileData $fileData
    ) {
        // Constructor
    }

    public function storageName(): ?string
    {
        return $this->fileData->storage ?? null;
    }

    public function __call($method, $parameters)
    {
        return $this->storageManager->$method($this, ...$parameters);
    }
}
