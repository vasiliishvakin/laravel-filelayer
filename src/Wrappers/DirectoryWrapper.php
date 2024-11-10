<?php

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Support\Collection;
use Vaskiq\LaravelFileLayer\Data\DirectoryData;
use Vaskiq\LaravelFileLayer\FileLayer;

/**
 * @extends FileSystemItemWrapper<DirectoryData, FileLayer>
 */
class DirectoryWrapper extends FileSystemItemWrapper
{
    /**
     * @return Collection<int, FileWrapper>
     */
    public function files(): ?Collection
    {
        return $this->data()->files;
    }

    /**
     * @return Collection<int, self>
     */
    public function directories(): ?Collection
    {
        return $this->data()->directories;
    }

    public function name(): string
    {
        return $this->data()->name;
    }
}
