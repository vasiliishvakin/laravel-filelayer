<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Traits\StorageManager;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

trait FileActions
{
    use FileInfo;
    use FindFile;

    abstract public function makeTmpFileWrapper(string $mime, ?string $content = null): TmpFileWrapper;

    public function register(FileWrapper $file, bool $forceRefresh = false): FileWrapper
    {
        if ($forceRefresh || $file->incomplete()) {
            $file->refresh();
        }

        $fileData = $this->getFileRepository()->save($file->data());

        return $this->makeFileWrapper($fileData);
    }

    public function relocate(FileWrapper $file, ?string $storageName = null, $options = []): FileWrapper
    {
        $fileData = $file->data();

        $storage = $this->getStorageOperator()->storage($storageName);

        $sourceName = $file->name();

        $newPath = $storage->putFileAs(
            path: $file->directory(),
            file: $file->laravelFile(),
            name: $file->name(),
        );

        $fileData = FileData::from([
            ...$fileData->toArray(),
            'path' => $newPath,
            'storage' => $storage->name,
            'source_name' => $sourceName,
        ]);

        return $this->makeFileWrapper($fileData);
    }

    public function sync(FileWrapper $file, bool $forceRefresh = false): FileWrapper
    {
        $dirty = false;

        if ($forceRefresh || $file->incomplete()) {
            $file->refresh();
            $dirty = true;
        }

        if ($file->storage() !== $this->getStorageOperator()->mainStorageName) {
            $file = $this->relocate($file, $this->getStorageOperator()->mainStorageName);
            $dirty = true;
        }

        if (! $file->id() || $dirty) {
            return $this->register($file);
        }

        return $file;
    }

    public function exists(FileWrapper $file): bool
    {
        $storage = $this->getStorageOperator()->storage($file->storage());

        return $storage->exists($file->path());
    }

    public function get(FileWrapper $file): ?string
    {
        $storage = $this->getStorageOperator()->storage($file->storage());

        return $storage->get($file->path());
    }

    public function delete(FileWrapper $file): bool
    {
        $storage = $this->getStorageOperator()->storage($file->storage());
        $deletedInStorage = $storage->exists($file->path()) ? $storage->delete($file->path()) : true;

        $deletedInDb = $file->id() !== null ? $this->getFileRepository()->delete($file->id()) : true;

        return $deletedInStorage && $deletedInDb;
    }

    public function put(string $path, string $content, ?string $storageName = null): FileWrapper
    {
        $this->fileByPath($path)?->delete();

        $storage = $this->getStorageOperator()->storage($storageName);

        if (! $storage->put($path, $content)) {
            throw new \Exception(sprintf('Failed to put file to storage %s', $storage->name));
        }

        $file = $this->makeFileWrapper(FileData::from([
            'path' => $path,
            'storage' => $storage->name,
        ]));

        return $this->register($file);
    }

    public function working(FileWrapper $file): FileWrapper
    {
        // if ($file->isLocal()) {
        //     return $file;
        // }
        $content = $this->get($file);

        return $this->makeTmpFileWrapper($file->mime(), $content);
    }
}
