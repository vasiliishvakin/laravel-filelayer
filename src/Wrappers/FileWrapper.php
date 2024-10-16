<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\StorageManager;
use Vaskiq\LaravelFileLayer\Wrappers\Traits\FileActions;
use Vaskiq\LaravelFileLayer\Wrappers\Traits\FileInfo;

class FileWrapper
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
        protected readonly StorageManager $manager,
        protected FileData $data,
    ) {}

    public function data(): FileData
    {
        return $this->data;
    }

    public function manager(): StorageManager
    {
        return $this->manager;
    }

    public function incomplete(): bool
    {
        $data = $this->data();
        foreach (self::REFRESHED_PROPERTIES as $property) {
            if (! property_exists($data, $property) || $data?->$property === null) {
                return true;
            }
        }

        return false;
    }

    public function refresh(): self
    {
        $properties = [
            'size' => $this->manager->size($this),
            'last_modified' => $this->manager->lastModified($this),
            'mime' => $this->manager->mime($this),
            'url' => $this->manager->url($this),
        ];

        $data = FileData::from([...$this->data->toArray(), ...$properties]);
        $this->data = $data;

        return $this;
    }

    public function sync()
    {
        return $this->manager->sync($this);
    }
}
