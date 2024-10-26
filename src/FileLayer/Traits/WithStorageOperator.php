<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer\Traits;

use Vaskiq\LaravelFileLayer\Storage\StorageOperator;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;

trait WithStorageOperator
{
    public function tmpStorage(): StorageWrapper
    {
        return $this->storageOperator()->tmp();
    }

    abstract private function storageOperator(): StorageOperator;

    private function storageByName(?string $name = null): StorageWrapper
    {
        return $this->storageOperator()->storage($name);
    }

    private function storageByFile(FileWrapper $file): StorageWrapper
    {
        return $this->storageOperator()->storage($file->storage());
    }
}
