<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Traits\StorageManager;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\StorageTools\StorageOperator;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

trait FindFile
{
    use BaseObjectsAccessors;

    public function file(int $id): FileWrapper
    {
        /** @var FileData */
        $fileData = $this->getFileRepository()->findOrFail($id);

        if ($fileData->storage) {
            if (! $this->getStorageOperator()->storage($fileData->storage)->exists($fileData->path)) {
                throw new \Exception('File not found');
            }

            return $this->makeFileWrapper($fileData);
        }

        foreach ($this->getStorageOperator()->storages() as $storageName => $storage) {
            if ($storage->exists($fileData->path)) {
                $fileDataWithStorage = FileData::from([
                    ...$fileData->toArray(),
                    'storage' => $storageName,
                ]);

                return $this->makeFileWrapper($fileData);
            }
        }

        throw new \Exception('File not found');
    }

    public function fileByPath(string $path): ?FileWrapper
    {
        $fileData = $this->getFileRepository()->findByPath($path);
        if ($fileData?->storage) {
            return $this->makeFileWrapper($fileData);
        }
        foreach ($this->getStorageOperator()->storages() as $storage) {
            if ($storage->name === StorageOperator::TMP_STORAGE_NAME) {
                continue;
            }
            if ($storage->exists($path)) {
                $fileData = FileData::from([
                    'path' => $path,
                    'storage' => $storage->name,
                ]);

                return $this->makeFileWrapper($fileData);
            }
        }

        return null;
    }
}
