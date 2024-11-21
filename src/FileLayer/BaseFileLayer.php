<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer;

use Carbon\CarbonImmutable;
use Illuminate\Http\File;
use League\Flysystem\Config as FlysystemConfig;
use Symfony\Component\Filesystem\Path;
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
    private const S3_ETAG_CONFIG = ['checksum_algo' => 'etag'];

    private FlysystemConfig $s3EtagConfig;

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
        $path = $this->normalizePath($path);

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

    public function getByPath(string $path, string|StorageWrapper|null $storage = null): string
    {
        return tap(
            $this->selectStorage($storage)->get($path),
            fn ($value) => Retrieved::dispatch($value)
        );
    }

    public function get(BaseFileWrapper $file): string
    {
        return $this->getByPath($this->path($file), $this->storageByFile($file));
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
        $storage = $this->selectStorage($storage);

        return tap(
            $this->selectStorage($storage)->exists($path),
            fn ($value) => CheckedExists::dispatch(['path' => $path, 'storage' => $storage->name, 'exists' => $value])
        );
    }

    public function delete(BaseFileWrapper $file): bool
    {
        return $this->storageByFile($file)->delete($this->path($file));
    }

    public function normalizePath(string $path): string
    {
        $path = ltrim($path, '/');

        return Path::normalize($path);
    }

    public function etag(BaseFileWrapper $file): string
    {
        return $this->isLocal($file)
            ? $this->calcStorageEtag($file)
            : $this->getEtagFromStorage($file) ?? $this->calcStorageEtag($file);
    }

    public function checkFileEtagInStorage(BaseFileWrapper $file, string|StorageWrapper|null $storage = null): bool
    {
        $fileEtag = $file->etag();
        if (! $fileEtag) {
            return false;
        }

        $storage = $storage ? $this->selectStorage($storage) : $this->storageByFile($file);
        $etag = $this->getEtagFromStorage($file, $storage) ?? $this->calcStorageEtag($file, $storage);

        return $file->etag() === $etag;
    }

    public function hash(BaseFileWrapper $file, string $hashName = self::HASH_ALGORITHM): string
    {
        return $this->isLocal($file)
            ? hash_file($hashName, $file->fullPath())
            : hash($hashName, $this->get($file));
    }

    protected function putToStorage(string $path, string $content, string|StorageWrapper|null $storage = null): bool
    {
        $path = $this->normalizePath($path);

        return $this->selectStorage($storage)->put($path, $content);
    }

    private function s3EtagConfig(): FlysystemConfig
    {
        return $this->s3EtagConfig ??= new FlysystemConfig(self::S3_ETAG_CONFIG);
    }

    private function getEtagFromStorage(BaseFileWrapper $file, string|StorageWrapper|null $storage = null): ?string
    {
        $storage = $storage ? $this->selectStorage($storage) : $this->storageByFile($file);

        /** @var \League\Flysystem\AwsS3V3\AwsS3V3Adapter|null */
        $adapter = $storage->adapter();
        if (! $adapter) {
            return null;
        }

        if (! $adapter instanceof \League\Flysystem\AwsS3V3\AwsS3V3Adapter) {
            report(new \InvalidArgumentException(sprintf(
                'Adapter %s is not supported for etag calculation for storage %s',
                get_class($adapter),
                $storage->name
            )));
        }

        try {
            return $adapter->checksum($file->path(), $this->s3EtagConfig());
        } catch (\Exception $e) {
            return null;
        }
    }

    private function calcStorageEtag(BaseFileWrapper $file, string|StorageWrapper|null $storage = null): string
    {
        $storage = $storage ? $this->selectStorage($storage) : $this->storageByFile($file);
        $path = $storage->path($this->path($file));

        return $this->storageOperator()->isLocal($storage)
            ? hash_file(self::ETAG_HASH_ALGORITHM, $path)
            : hash(self::ETAG_HASH_ALGORITHM, $this->getByPath($path));
    }
}
