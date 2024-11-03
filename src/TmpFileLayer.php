<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer;

use Illuminate\Support\Str;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Exceptions\TmpFileExistsException;
use Vaskiq\LaravelFileLayer\Facades\Mime;
use Vaskiq\LaravelFileLayer\Storage\StorageOperator;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

/**
 * @extends BaseFileLayer<TmpFileWrapper>
 */
final class TmpFileLayer extends BaseFileLayer
{
    /** @var array<string, TmpFileWrapper> */
    protected array $tmpFiles = [];

    public function __construct(
        StorageOperator $storageOperator,
    ) {
        parent::__construct($storageOperator);
        $this->registerShutdownHandler();
    }

    public function create(?string $content = null, ?string $mime = null): TmpFileWrapper
    {
        $extension = $mime ? Mime::extension($mime) : null;

        $filePath = $this->createFile($content, $extension);

        $tmpData = FileData::from([
            'storage' => $this->storage()->name,
            'path' => $filePath,
            'mime' => $mime,
        ]);

        $file = TmpFileWrapper::from($tmpData, $this);

        if ($this->existByKey($file->toKey())) {
            throw TmpFileExistsException::fromPath($file->path(), $file->storage());
        }
        $this->tmpFiles[$file->toKey()] = $file;

        return $file;
    }

    public function delete(TmpFileWrapper $file): void
    {
        if ($this->exists($file)) {
            $this->storage()->delete($file->path());
        }
        unset($this->tmpFiles[$file->toKey()]);
    }

    private function storage(): StorageWrapper
    {
        return $this->storageOperator()->tmp();
    }

    private function createFile(?string $content = null, ?string $extension = null): string
    {
        $content ??= '';
        $storage = $this->storage();
        $extension = $extension ? '.'.ltrim($extension, '.') : '';

        do {
            $fileName = Str::ulid().$extension;
        } while ($storage->exists($fileName));

        if (! $storage->put($fileName, $content)) {
            throw new \RuntimeException(sprintf('Failed to create a temporary file in the storage %s.', $storage->name));
        }

        return $fileName;
    }

    private function clear(): void
    {
        foreach ($this->tmpFiles as $file) {
            $this->delete($file);
        }
        $this->tmpFiles = [];
    }

    private function existByKey(string $key): bool
    {
        return array_key_exists($key, $this->tmpFiles);
    }

    private function registerShutdownHandler(): void
    {
        register_shutdown_function(\Closure::fromCallable([$this, 'clear']));
    }
}
