<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Carbon\CarbonImmutable;
use Vaskiq\LaravelFileLayer\Contracts\BaseFileWrapperInterface;
use Vaskiq\LaravelFileLayer\Data\BaseFileData;
use Vaskiq\LaravelFileLayer\Facades\Mime;
use Vaskiq\LaravelFileLayer\FileLayer\BaseFileLayer;

/**
 * @template TData of BaseFileData
 * @template TFileLayer of BaseFileLayer
 *
 * @extends FileSystemItemWrapper<TData, TFileLayer>
 */
class BaseFileWrapper extends FileSystemItemWrapper implements BaseFileWrapperInterface
{
    public function directory(): string
    {
        return dirname($this->path());
    }

    public function name(): string
    {
        return basename($this->path());
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->path(), PATHINFO_EXTENSION));
    }

    public function mimeExtension(): string
    {
        return Mime::extension($this->mime());
    }

    public function cleanName(): string
    {
        return pathinfo($this->path(), PATHINFO_FILENAME);
    }

    public function size(): int
    {
        return $this->data()->size ?? $this->fileLayer()->size($this);
    }

    public function lastModified(): CarbonImmutable
    {
        return $this->data()->last_modified ?? $this->fileLayer()->lastModified($this);
    }

    public function mime(): string
    {
        return $this->data()->mime ?? $this->fileLayer()->mime($this);
    }

    public function url(): string
    {
        return $this->data()->url ?? $this->fileLayer()->url($this);
    }

    public function get(): ?string
    {
        return $this->fileLayer()->get($this);
    }

    public function exists(): bool
    {
        return $this->fileLayer()->exists($this);
    }

    public function delete(): bool
    {
        return $this->fileLayer()->delete($this);
    }

    public function etag(): string
    {
        return $this->data()->etag ?? $this->fileLayer()->etag($this);
    }

    public function hash(): string
    {
        return $this->data()->hash ?? $this->fileLayer()->hash($this, $this->hashName());
    }

    public function hashName(): string
    {
        return $this->data()->hash_name ?? $this->fileLayer()::HASH_ALGORITHM;
    }

    public function getProperty(string $name, mixed $default = null): mixed
    {
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }

        if (property_exists($this->data(), $name)) {
            return $this->data()->{$name};
        }

        return $default;
    }
}
