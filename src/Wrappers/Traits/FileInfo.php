<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers\Traits;

use Carbon\CarbonImmutable;
use Illuminate\Http\File as LaravelFile;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Facades\Mime;
use Vaskiq\LaravelFileLayer\StorageManager;

trait FileInfo
{
    abstract public function data(): FileData;

    abstract public function manager(): StorageManager;

    public function id(): ?int
    {
        return $this->data->id ?? null;
    }

    public function storage(): ?string
    {
        return $this->data->storage ?? null;
    }

    public function path(): string
    {
        return $this->data->path;
    }

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
        return $this->data->size ?? $this->manager()->size($this);
    }

    public function lastModified(): CarbonImmutable
    {
        return $this->data->lastModified ?? $this->manager()->lastModified($this);
    }

    public function mime(): string
    {
        return $this->data->mimeType ?? $this->manager()->mime($this);
    }

    public function laravelFile(): LaravelFile
    {
        return $this->manager()->laravelFile($this);
    }

    public function url(): string
    {
        return $this->data->url ?? $this->manager()->url($this);
    }

    public function isLocal(): bool
    {
        return $this->manager()->isLocal($this);
    }
}
