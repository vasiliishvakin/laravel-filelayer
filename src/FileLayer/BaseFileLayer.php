<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer;

use Carbon\CarbonImmutable;
use Illuminate\Http\File;
use Vaskiq\LaravelFileLayer\Contracts\FileLayerInterface;
use Vaskiq\LaravelFileLayer\Contracts\FileSystemItemWrapperInterface;
use Vaskiq\LaravelFileLayer\Data\PathInfoData;
use Vaskiq\LaravelFileLayer\Events\CheckedExists;
use Vaskiq\LaravelFileLayer\Events\Retrieved;
use Vaskiq\LaravelFileLayer\Storage\StorageOperator;
use Vaskiq\LaravelFileLayer\Wrappers\BaseFileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;

/**
 * @template TFileWrapper of FileSystemItemWrapperInterface
 */
class BaseFileLayer implements FileLayerInterface
{
    public function __construct(
        private readonly StorageOperator $storageOperator,
    ) {}

    public function storageOperator(): StorageOperator
    {
        return $this->storageOperator;
    }

    public function selectStorage(string|StorageWrapper|null $storage = null): StorageWrapper
    {
        return $storage instanceof StorageWrapper
            ? $storage
            : $this->storageByName($storage);
    }

    public function storageByName(?string $name = null): StorageWrapper
    {
        return $this->storageOperator()->storage($name);
    }

    public function storageByFile(FileSystemItemWrapperInterface $file): StorageWrapper
    {
        return $this->storageOperator()->storage($file->storage());
    }

    public function path(FileSystemItemWrapperInterface $file): string
    {
        return $file->data()->path;
    }

    public function fullPath(FileSystemItemWrapperInterface $file): string
    {
        return $this->storageByFile($file)->path($this->path($file));
    }

    public function isLocal(FileSystemItemWrapperInterface $file): bool
    {
        return $this->storageOperator()->isLocal($this->storageByFile($file));
    }

    public function exists(FileSystemItemWrapperInterface $file): bool
    {
        return $this->storageByFile($file)->exists($this->path($file));
    }

    public function pathInfo(string $path, StorageWrapper|string|null $storage = null): PathInfoData
    {
        $isDirectory = null;

        $storage = $this->selectStorage($storage);
        if ($storage->exists($path) && $this->storageOperator()->isLocal($storage)) {
            $fullPath = $storage->path($path);
            $isDirectory = is_dir($fullPath);
        }

        return PathInfoData::from([
            'path' => $path,
            'isDirectory' => $isDirectory,
        ]);
    }

    public function size(BaseFileWrapper $file): int
    {
        return $this->storageByFile($file)->size($this->path($file));
    }

    public function mime(BaseFileWrapper $file): string|false
    {
        return $this->storageByFile($file)->mimeType($this->path($file));
    }

    public function url(BaseFileWrapper $file): string
    {
        return $this->storageByFile($file)->url($this->path($file));
    }

    public function lastModified(BaseFileWrapper $file): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp(
            $this->storageByFile($file)->lastModified($this->path($file))
        );
    }

    public function get(BaseFileWrapper $file): string
    {
        return tap(
            $this->storageByFile($file)->get($this->path($file)),
            fn ($value) => Retrieved::dispatch($value)
        );
    }

    public function laravelFile(BaseFileWrapper $file): File
    {
        $storage = $this->storageByFile($file);
        if (! $this->storageOperator()->isLocal($storage)) {
            throw new \Exception('Storage is not local');
        }

        return new File($storage->path($this->path($file)));
    }

    public function existsPath(string $path, string|StorageWrapper|null $storage = null): bool
    {
        return tap(
            $this->selectStorage($storage)->exists($path),
            fn ($value) => CheckedExists::dispatch(['path' => $path, 'storage' => $storage->name, 'exists' => $value])
        );
    }

    public function delete(BaseFileWrapper $file): bool
    {
        return $this->storageByFile($file)->delete($this->path($file));
    }

    protected function putToStorage(string $path, string $content, string|StorageWrapper|null $storage = null): bool
    {
        return $this->selectStorage($storage)->put($path, $content);
    }
}
