<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer;

use Vaskiq\LaravelFileLayer\FileLayer\Traits\FileActions;
use Vaskiq\LaravelFileLayer\FileLayer\Traits\FileInfo;
use Vaskiq\LaravelFileLayer\FileLayer\Traits\FindFile;
use Vaskiq\LaravelFileLayer\FileLayer\Traits\WithFileRepository;
use Vaskiq\LaravelFileLayer\FileLayer\Traits\WithFileWrappers;
use Vaskiq\LaravelFileLayer\FileLayer\Traits\WithStorageOperator;
use Vaskiq\LaravelFileLayer\Repositories\FileRepository;
use Vaskiq\LaravelFileLayer\Storage\StorageOperator;

final class FileLayer
{
    use FileActions;
    use FileInfo;
    use FindFile;
    use WithFileRepository;
    use WithFileWrappers;
    use WithStorageOperator;

    public function __construct(
        private readonly StorageOperator $storageOperator,
        private readonly FileRepository $fileRepository,
    ) {}

    private function storageOperator(): StorageOperator
    {
        return $this->storageOperator;
    }

    private function fileRepository(): FileRepository
    {
        return $this->fileRepository;
    }
}
