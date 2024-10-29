<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Support\Facades\App;
use Vaskiq\LaravelFileLayer\Contracts\FileWrapperInterface;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\FileLayer;
use Vaskiq\LaravelFileLayer\Wrappers\Traits\FileActions;
use Vaskiq\LaravelFileLayer\Wrappers\Traits\FileInfo;

class FileWrapper implements FileWrapperInterface
{
    use FileActions;
    use FileInfo;

    protected const REFRESHED_PROPERTIES = [
        'size',
        'last_modified',
        'mime',
        'url',
    ];

    public function __construct(
        protected FileData $data,
        protected readonly FileLayer $manager,
    ) {}

    public static function fromData(FileData $data, ?FileLayer $manager = null): self
    {
        $manager ??= App::make(FileLayer::class);

        return new self($data, $manager);
    }

    public function data(): FileData
    {
        return $this->data;
    }

    public function manager(): FileLayer
    {
        return $this->manager;
    }

    public function incomplete(): bool
    {
        $data = $this->data();
        foreach (self::REFRESHED_PROPERTIES as $property) {
            if (! property_exists($data, $property) || $data->$property === null) {
                return true;
            }
        }

        return false;
    }

    public function refresh(array|string|null $properties  = null): self
    {
        //TODO: use Enum
        $updaters = [
            'size' => fn() => $this->manager->size($this),
            'last_modified' => fn() => $this->manager->lastModified($this),
            'mime' => fn() => $this->manager->mime($this),
            'url' => fn() => $this->manager->url($this),
        ];

        $properties ??= self::REFRESHED_PROPERTIES;

        $fileProperties = [];
        foreach ((array)$properties as $property) {
            if (array_key_exists($property, $updaters)) {
                $fileProperties[$property] = $updaters[$property]();
            }
        }

        $data = FileData::from([...$this->data->toArray(), ...$fileProperties]);
        $this->data = $data;

        return $this;
    }

    public function misplaced(): bool
    {
        return $this->manager->misplaced($this);
    }

    public function sync(): FileWrapper
    {
        return $this->manager->sync($this);
    }

    public function working(): static|FileWrapperInterface
    {
        return $this->manager()->working($this);
    }

    public function process(array $actions): static|FileWrapperInterface
    {
        return $this->manager()->process($this, $actions);
    }

    public function processTo(array $actions): static|FileWrapperInterface
    {
        return $this->manager()->processTo($this, $actions);
    }
}
