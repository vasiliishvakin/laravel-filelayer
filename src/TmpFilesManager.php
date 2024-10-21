<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer;

use Vaskiq\LaravelFileLayer\Exceptions\TmpFileExistsException;
use Vaskiq\LaravelFileLayer\StorageTools\StorageOperator;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

class TmpFilesManager
{
    /** @var array<string, TmpFileWrapper> */
    protected array $tmpFiles = [];

    public function __construct(
        private readonly StorageOperator $storageOperator,
    ) {
        $this->registerShutdownHandler();
    }

    public function getStorageOperator(): StorageOperator
    {
        return $this->storageOperator;
    }

    public function storage(TmpFileWrapper $file): StorageWrapper
    {
        return $this->getStorageOperator()->storage($file->storage());
    }

    protected function existByKey(string $key): bool
    {
        return array_key_exists($key, $this->tmpFiles);
    }

    public function create(?string $content = null, ?string $mime = null, ?StorageManager $manager = null): TmpFileWrapper
    {
        $file = TmpFileWrapper::fromContent(content: $content, mime: $mime);

        if ($this->existByKey($file->toKey())) {
            throw TmpFileExistsException::fromPath($file->path(), $file->storage());
        }
        $this->tmpFiles[$file->toKey()] = $file;

        return $file;
    }

    public function delete(TmpFileWrapper $file): void
    {
        $storage = $this->storage($file);
        if ($storage->exists($file->path())) {
            $storage->delete($file->path());
        }
        unset($this->tmpFiles[$file->toKey()]);
    }

    public function clear(): void
    {
        foreach ($this->tmpFiles as $file) {
            $this->delete($file);
        }
        $this->tmpFiles = [];
    }

    protected function registerShutdownHandler(): void
    {
        register_shutdown_function([$this, 'clear']);
    }
}
