<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Traits\StorageManager;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Repositories\fileRepository;
use Vaskiq\LaravelFileLayer\StorageTools\storageOperator;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;

trait BaseObjectsAccessors
{
    abstract public function getStorageOperator(): StorageOperator;

    abstract public function getFileRepository(): FileRepository;

    abstract public function makeFileWrapper(FileData $fileData): FileWrapper;

    protected function fileStorage(FileWrapper $file): StorageWrapper
    {
        return $this->getStorageOperator()->storage($file->storage());
    }
}
