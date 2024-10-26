<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers\Traits;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\FileLayer;

trait FileActions
{
    abstract public function data(): FileData;

    abstract public function manager(): FileLayer;

    public function get(): ?string
    {
        return $this->manager()->get($this);
    }

    public function exists(): bool
    {
        return $this->manager()->exists($this);
    }

    public function delete(): bool
    {
        return $this->manager()->delete($this);
    }
}
