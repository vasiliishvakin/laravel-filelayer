<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Facades\TmpFile;
use Vaskiq\LaravelFileLayer\Repositories\FileRepository;
use Vaskiq\LaravelFileLayer\StorageTools\FileProcessOperator;
use Vaskiq\LaravelFileLayer\StorageTools\StorageOperator;
use Vaskiq\LaravelFileLayer\Traits\StorageManager\FileActions;
use Vaskiq\LaravelFileLayer\Traits\StorageManager\FileInfo;
use Vaskiq\LaravelFileLayer\Traits\StorageManager\FindFile;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

class StorageManager
{
    use FileActions;
    use FileInfo;
    use FindFile;

    public function __construct(
        private readonly StorageOperator $storageOperator,
        private readonly FileRepository $fileRepository,
        private readonly FileProcessOperator $fileProcessOperator,
    ) {}

    public function getStorageOperator(): StorageOperator
    {
        return $this->storageOperator;
    }

    public function getFileRepository(): FileRepository
    {
        return $this->fileRepository;
    }

    public function getFileProcessOperator(): FileProcessOperator
    {
        return $this->fileProcessOperator;
    }

    protected function makeFileWrapper(FileData $fileData): FileWrapper
    {
        return new FileWrapper($this, $fileData);
    }

    protected function makeTmpFileWrapper(
        ?string $content = null,
        ?string $mime = null
    ): TmpFileWrapper {
        return TmpFile::create(content: $content, mime: $mime);
    }

    protected function storage(FileWrapper $file): StorageWrapper
    {
        return $this->storageOperator->storage($file->storage());
    }
}
