<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;

/**
 * @mixin Filesystem
 * @mixin FilesystemAdapter
 */
class StorageWrapper
{
    public function __construct(
        public readonly string $name,
        /** @var Filesystem|FilesystemAdapter */
        public readonly Filesystem $storage,
    ) {}

    public function __call(string $method, array $parameters): mixed
    {
        return $this->storage->{$method}(...$parameters);
    }
}
