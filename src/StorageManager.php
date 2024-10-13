<?php

namespace Vaskiq\LaravelFileLayer;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Monolog\Processor\ProcessorInterface;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Repositories\FileRepository;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

/**
 * @mixin Filesystem
 */
class StorageManager
{
    public readonly string $mainStorageName;

    protected readonly Filesystem $mainStorage;

    protected readonly array $storagesConfig;

    public function __construct(
        protected readonly Container $app,
        protected readonly FilesystemFactory $filesystemFactory,
        protected readonly Config $config,
        protected readonly FileRepository $fileRepository,
    ) {
        $storages = $this->config->get('filesystems.disks', []);
        usort($storages, fn ($a, $b) => ($a['priority'] ?? PHP_INT_MAX) <=> ($b['priority'] ?? PHP_INT_MAX));
        $this->storagesConfig = $storages;

        foreach ($storages as $name => $storage) {
            if (($storage['read_only'] ?? false) === false) {
                $this->mainStorageName = $name;
                break;
            }
        }

        $this->mainStorage = $this->storage($this->mainStorageName);
    }

    public function storage(string $name): Filesystem
    {
        return $this->filesystemFactory->disk($name);
    }

    /**
     * @return Iterator|Filesystem[]
     */
    public function storages(): \Iterator
    {
        foreach ($this->storagesConfig as $name => $storage) {
            yield $name => $this->storage($name);
        }
    }

    public function processorsAll(): array
    {
        return $this->app->tagged(ProcessorInterface::class);
    }

    public function processors(FileWrapper $file): array
    {
        // return array_filter($this->processorsAll(), fn(ProcessorInterface $processor) => $processor->isSupported($mime));
        return [];
    }

    public function __call($name, $arguments)
    {
        $storage = $arguments && $arguments[0] instanceof FileWrapper && $arguments[0]->storageName()
            ? $this->storage($arguments[0]->storageName())
            : $this->mainStorage;

        if (! method_exists($storage, $name)) {
            throw new \Exception('Method not found');
        }

        return $storage->$name(...$arguments);
    }

    protected function createFileWrapper(FileData $fileData, ?Filesystem $storage = null): FileWrapper
    {
        return new FileWrapper($this, $fileData, $storage);
    }

    public function file(int $id): FileWrapper
    {
        /** @var FileData */
        $fileData = $this->fileRepository->findOrFail($id);
        if ($fileData->storage) {
            if (! $this->storage($fileData->storage)->exists($fileData->path)) {
                throw new \Exception('File not found');
            }

            return $this->createFileWrapper($fileData);
        }

        foreach ($this->storages() as $storageName => $storage) {
            if ($storage->exists($fileData->path)) {
                $fileDataWithStorage = FileData::from([
                    ...$fileData->toArray(),
                    'storage' => $storageName,
                ]);
                $this->fileRepository->save($fileDataWithStorage);

                return $this->createFileWrapper($fileData, $storage);
            }
        }

        throw new \Exception('File not found');
    }

    public function fileByPath(string $path, ?string $storage = null): FileWrapper
    {
        $fileData = $this->fileRepository->findByPath($path, $storage);
        if (! $fileData) {
            throw new \Exception('File not found');
        }

        return $this->createFileWrapper($fileData);
    }

    public function fileByAlias(string $alias): FileWrapper
    {
        $fileData = $this->fileRepository->findByAlias($alias);
        if (! $fileData) {
            throw new \Exception('File not found');
        }

        return $this->createFileWrapper($fileData);
    }

    public function uploadLocal(string $localPath, string $newPath, ?string $storageName = null)
    {
        $storage = $storageName ? $this->storage($storageName) : $this->mainStorage;
        $storage->put($newPath, file_get_contents($localPath));
    }
}
