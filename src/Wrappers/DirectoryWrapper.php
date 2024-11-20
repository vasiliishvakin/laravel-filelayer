<?php

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Support\Collection;
use Vaskiq\LaravelFileLayer\Data\DirectoryData;
use Vaskiq\LaravelFileLayer\FileLayer;

/**
 * @mixin DirectoryData
 * @extends FileSystemItemWrapper<DirectoryData, FileLayer>
 */
class DirectoryWrapper extends FileSystemItemWrapper
{

    public function __get($name)
    {
        if (property_exists($this->data(), $name)) {
            return $this->data()->$name;
        }

        throw new \Exception("Property $name does not exist");
    }

    /**
     * @deprecated
     * @return Collection<int, FileWrapper>
     */
    public function files(): ?Collection
    {
        return $this->data()->files;
    }

    /**
     * @deprecated
     * @return Collection<int, self>
     */
    public function directories(): ?Collection
    {
        return $this->data()->directories;
    }

    /**
     * @deprecated
     */
    public function name(): string
    {
        return $this->data()->name;
    }

    public function count(): ?int
    {
        if ($this->files?->isEmpty() && $this->directories?->isEmpty()) {
            return null;
        }

        return ($this->directories?->count() ?? 0) + ($this->files?->count() ?? 0);
    }

    public function emptyFiles(): bool
    {
        return $this->files?->isEmpty() ?? true;
    }

    public function emptyDirectories(): bool
    {
        return $this->directories?->isEmpty() ?? true;
    }

    public function empty(): bool
    {
        return $this->emptyFiles() && $this->emptyDirectories();
    }
}
