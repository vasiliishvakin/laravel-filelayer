<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\StorageTools;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use League\Flysystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;

class StorageOperator
{
    protected readonly array $storagesConfig;

    protected readonly array $storagesNames;

    // protected readonly StorageWrapper $mainStorage;

    public readonly string $mainStorageName;

    private array $initStorages = [];

    public function __construct(
        protected readonly Config $config,
        protected readonly FilesystemFactory $filesystemFactory,
    ) {
        $storages = $this->config->get('filesystems.disks', []);
        uasort($storages, fn ($a, $b) => ($a['priority'] ?? PHP_INT_MAX) <=> ($b['priority'] ?? PHP_INT_MAX));
        $this->storagesConfig = $storages;
        $this->storagesNames = array_keys($storages);

        $defaultStorage = $this->config->get('filesystems.default');
        if ($storages[$defaultStorage]['read_only'] ?? false) {
            throw new \Exception('Default storage is read-only');
        }

        $this->mainStorageName = $defaultStorage;

        //idea: in the future we can use multiple main storages fo shards

        $this->initStorages[$this->mainStorageName] = $this->makeStorageWrapper($defaultStorage);
    }

    protected function makeStorageWrapper(string $name): StorageWrapper
    {
        return new StorageWrapper(
            name: $name,
            storage: $this->filesystemFactory->disk($name),
        );
    }

    public function config(?string $name = null): ?array
    {
        return $name ? $this->storagesConfig[$name] ?? null : $this->storagesConfig;
    }

    public function mainStorage(): StorageWrapper
    {
        return $this->storage($this->mainStorageName);
    }

    public function storage($name = null): StorageWrapper
    {
        $name = $name ?: $this->mainStorageName;

        return $this->initStorages[$name] ??= $this->makeStorageWrapper($name);
    }

    /**
     * @return Iterator|StorageWrapper[]
     */
    public function storages(): \Iterator
    {
        foreach ($this->storagesNames as $name) {
            yield $this->storage($name);
        }
    }

    public function adapter(StorageWrapper $storage): LaravelFilesystemAdapter
    {
        return $storage->getAdapter();
    }

    public function isLocal(StorageWrapper $storage): bool
    {
        $adapter = $this->adapter($storage);

        return match (true) {
            $adapter instanceof \League\Flysystem\Local\LocalFilesystemAdapter => true,
            default => false,
        };
    }
}
