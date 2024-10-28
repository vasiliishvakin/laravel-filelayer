<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer\Traits;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Data\PathInfoData;
use Vaskiq\LaravelFileLayer\Storage\StorageOperator;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

trait FindFile
{
    use FileInfo;
    use WithFileRepository;
    use WithFileWrappers;
    use WithStorageOperator;

    public function file(int $id): FileWrapper
    {
        /** @var FileData */
        $fileData = $this->fileRepository()->findOrFail($id);

        if ($fileData->storage) {
            if (! $this->storageOperator()->storage($fileData->storage)->exists($fileData->path)) {
                throw new \Exception('File not found');
            }

            return $this->makeFileWrapper($fileData);
        }

        foreach ($this->storageOperator()->storages() as $storageName => $storage) {
            if ($storage->exists($fileData->path)) {
                $fileDataWithStorage = FileData::from([
                    ...$fileData->toArray(),
                    'storage' => $storageName,
                ]);

                return $this->makeFileWrapper($fileDataWithStorage);
            }
        }

        throw new \Exception('File not found');
    }

    public function fileByPath(string $path, ?string $storageName = null): ?FileWrapper
    {
        $fileData = $this->fileRepository()->findByPath($path, $storageName);
        if ($fileData?->storage) {
            if (! is_null($storageName) && $fileData->storage !== $storageName) {
                return null;
            }

            return $this->makeFileWrapper($fileData);
        }

        foreach ($this->storageOperator()->storages() as $storage) {
            if ($storage->name === StorageOperator::TMP_STORAGE_NAME) {
                continue;
            }
            if ($storage->exists($path)) {
                if ($this->storageOperator()->isLocal($storage)) {
                    $fullPath = $storage->path($path);
                    $isDirectory = is_dir($fullPath);

                    $pathInfoData = PathInfoData::from([
                        'path' => $path,
                        'isDirectory' => $isDirectory,
                    ]);
                }

                $fileData = FileData::from([
                    'path' => $path,
                    'storage' => $storage->name,
                    'directory' => isset($pathInfoData) ? $pathInfoData->directory : null, //$pathInfoData?->directory,
                ]);

                return $this->makeFileWrapper($fileData);
            }
        }

        return null;
    }
}
