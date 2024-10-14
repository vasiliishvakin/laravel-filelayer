<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Traits\StorageManager;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

trait FileActions
{
    use FileInfo;
    use FindFile;

    public function register(FileWrapper $file, bool $forceRefresh = false): FileWrapper
    {
        if ($forceRefresh || $file->incomplete()) {
            $file->refresh();
        }

        $this->getFileRepository()->save($file->data());

        return $file;
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
        if (! $storage->delete($file->path())) {
            throw new \Exception(sprintf('Failed to delete file from storage %s', $storage->name));
        }

        return $this->getFileRepository()->delete($file->id());
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
}