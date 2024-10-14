<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Traits\StorageManager;

use Carbon\CarbonImmutable;
use Illuminate\Http\File as LaravelFile;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

trait FileInfo
{
    use BaseObjectsAccessors;

    protected function filePath(FileWrapper $file): string
    {
        return $file->data()->path;
    }

    public function size(FileWrapper $file): int
    {
        return $this->fileStorage($file)->size($this->filePath($file));
    }

    public function mime(FileWrapper $file): string
    {
        return $this->fileStorage($file)->mimeType($this->filePath($file));
    }

    public function lastModified(FileWrapper $file): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp(
            $this->fileStorage($file)->lastModified($this->filePath($file))
        );
    }

    public function url(FileWrapper $file): string
    {
        return $this->fileStorage($file)->url($this->filePath($file));
    }

    public function laravelFile(FileWrapper $file): LaravelFile
    {
        $storage = $this->fileStorage($file);
        if (! $this->getStorageOperator()->isLocal($storage)) {
            throw new \Exception('Storage is not local');
        }

        return new LaravelFile($storage->path($this->filePath($file)));
    }

    public function existsPath(string $path, ?string $storageName = null): bool
    {
        $storage = $storageName
            ? $this->getStorageOperator()->storage($storageName)
            : $this->getStorageOperator()->storage($storageName);

        return $storage->exists($path);
    }
}
