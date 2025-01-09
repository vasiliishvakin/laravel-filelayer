<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Support\Facades\App;
use Vaskiq\LaravelFileLayer\Contracts\FileLayerInterface;
use Vaskiq\LaravelFileLayer\Contracts\FileSystemItemWrapperInterface;
use Vaskiq\LaravelFileLayer\Data\FileSystemItemData;
use Vaskiq\LaravelFileLayer\FileLayer;

/**
 * @template TData of FileSystemItemData
 * @template TFileLayer of FileLayerInterface
 */
class FileSystemItemWrapper implements FileSystemItemWrapperInterface
{
    /**
     * @param  TData  $data
     * @param  TFileLayer  $fileLayer
     */
    protected function __construct(
        protected readonly FileSystemItemData $data,
        protected readonly FileLayerInterface $fileLayer,
    ) {}

    /**
     * @param  TData  $data
     * @param  TFileLayer  $fileLayer
     */
    public static function from(FileSystemItemData $data, ?FileLayerInterface $fileLayer): static
    {
        $fileLayer ??= App::make(FileLayer::class);

        return new static($data, $fileLayer);
    }

    /**
     * @return TData
     */
    public function data(): FileSystemItemData
    {
        return $this->data;
    }

    /**
     * @return TFileLayer
     */
    public function fileLayer(): FileLayerInterface
    {
        return $this->fileLayer;
    }

    public function storage(): ?string
    {
        return $this->data()->storage;
    }

    public function path(): ?string
    {
        return $this->data()->path;
    }

    public function toKey(): string
    {
        return $this->storage() . ':' . $this->path();
    }

    public function __toString(): string
    {
        return (string) $this->path();
    }

    public function isLocal(): bool
    {
        return $this->fileLayer()->isLocal($this);
    }

    public function fullPath(): string
    {
        return $this->fileLayer()->fullPath($this);
    }

    public function name(): string
    {
        throw new \BadMethodCallException('Method not implemented');
    }

    public function extension(): ?string
    {
        throw new \BadMethodCallException('Method not implemented');
    }

    public function size(): ?int
    {
        throw new \BadMethodCallException('Method not implemented');
    }

    public function mime(): ?string
    {
        throw new \BadMethodCallException('Method not implemented');
    }
}
