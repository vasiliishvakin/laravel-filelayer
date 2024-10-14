<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Repositories\FileRepository;
use Vaskiq\LaravelFileLayer\StorageTools\FileProcessOperator;
use Vaskiq\LaravelFileLayer\StorageTools\StorageOperator;
use Vaskiq\LaravelFileLayer\Traits\StorageManager\FileActions;
use Vaskiq\LaravelFileLayer\Traits\StorageManager\FileInfo;
use Vaskiq\LaravelFileLayer\Traits\StorageManager\FindFile;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;

class StorageManager
{
    use FileActions;
    use FileInfo;
    use FindFile;

    public function __construct(
        protected readonly StorageOperator $storageOperator,
        protected readonly FileRepository $fileRepository,
        protected readonly FileProcessOperator $fileProcessOperator,
    ) {}

    public function getStorageOperator(): StorageOperator
    {
        return $this->storageOperator;
    }

    public function getFileRepository(): FileRepository
    {
        return $this->fileRepository;
    }

    protected function makeFileWrapper(FileData $fileData): FileWrapper
    {
        return new FileWrapper($this, $fileData);
    }

    protected function storage(FileWrapper $file): StorageWrapper
    {
        return $this->storageOperator->storage($file->storage());
    }
}
