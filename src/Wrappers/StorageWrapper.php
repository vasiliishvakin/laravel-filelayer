<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapter as FlysystemFilesystemAdapter;

/**
 * @mixin Filesystem
 * @mixin FilesystemAdapter
 * @mixin AwsS3V3Adapter
 */
class StorageWrapper
{
    public function __construct(
        public readonly string $name,
        /** @var Filesystem|FilesystemAdapter|AwsS3V3Adapter */
        public readonly Filesystem $storage,
    ) {}

    /**
     * @param  array<mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->storage->{$method}(...$parameters);
    }

    public function adapter(): ?FlysystemFilesystemAdapter
    {
        if (! method_exists($this->storage, 'getAdapter')) {
            return null;
        }

        return $this->storage->{'getAdapter'}();
    }
}
