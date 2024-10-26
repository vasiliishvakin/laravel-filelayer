<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer\Traits;

use Carbon\CarbonImmutable;
use Illuminate\Http\File as LaravelFile;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

trait FileInfo
{
    use WithStorageOperator;

    public function size(FileWrapper $file): int
    {
        return $this->storageByFile($file)->size($this->filePath($file));
    }

    public function mime(FileWrapper $file): string|false
    {
        return $this->storageByFile($file)->mimeType($this->filePath($file));
    }

    public function lastModified(FileWrapper $file): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp(
            $this->storageByFile($file)->lastModified($this->filePath($file))
        );
    }

    public function url(FileWrapper $file): string
    {
        return $this->storageByFile($file)->url($this->filePath($file));
    }

    public function laravelFile(FileWrapper $file): LaravelFile
    {
        $storage = $this->storageByFile($file);
        if (! $this->storageOperator()->isLocal($storage)) {
            throw new \Exception('Storage is not local');
        }

        return new LaravelFile($storage->path($this->filePath($file)));
    }

    public function existsPath(string $path, ?string $storage = null): bool
    {
        return $this->storageByName($storage)->exists($path);
    }

    public function isLocal(FileWrapper $file): bool
    {
        return $this->storageOperator()->isLocal($this->storageByFile($file));
    }

    public function fullPath(FileWrapper $file): string
    {
        return $this->storageByFile($file)->path($this->filePath($file));
    }

    public function content(FileWrapper $file): ?string
    {
        return $this->storageByFile($file)->get($this->filePath($file));
    }

    public function misplaced(FileWrapper $file): bool
    {
        return $file->storage() !== $this->storageOperator()->mainStorageName;
    }

    public function exists(FileWrapper $file): bool
    {
        $storage = $this->storageOperator()->storage($file->storage());

        return $storage->exists($file->path());
    }

    protected function filePath(FileWrapper $file): string
    {
        return $file->data()->path;
    }
}
