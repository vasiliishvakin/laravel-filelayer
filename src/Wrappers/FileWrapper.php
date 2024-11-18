<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Http\File;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Enums\FileRefreshedProperty;
use Vaskiq\LaravelFileLayer\Events\Refreshed;
use Vaskiq\LaravelFileLayer\FileLayer;

/**
 * @extends BaseFileWrapper<FileData, FileLayer>
 */
class FileWrapper extends BaseFileWrapper
{
    protected string $workingPath;

    public function repositoryId(): int|string|null
    {
        return $this->data()?->id ?? null;
    }

    public function incomplete(): bool
    {
        $data = $this->data();
        foreach ($this->refreshedProperties() as $property) {
            $filePropertyName = $property->value;
            if (
                ! property_exists($data, $filePropertyName)
                || $data->{$filePropertyName} === null
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<FileRefreshedProperty>|FileRefreshedProperty|null  $properties
     */
    public function refresh(array|FileRefreshedProperty|null $properties = null): self
    {
        $updaters = [
            FileRefreshedProperty::SIZE->value => fn() => $this->fileLayer()->size($this),
            FileRefreshedProperty::LAST_MODIFIED->value => fn() => $this->fileLayer()->lastModified($this),
            FileRefreshedProperty::MIME->value => fn() => $this->fileLayer()->mime($this),
            FileRefreshedProperty::URL->value => fn() => $this->fileLayer()->url($this),
            FileRefreshedProperty::ETAG->value => fn() => $this->fileLayer()->etag($this),
        ];

        $properties = $properties ?? $this->refreshedProperties();
        if (! is_array($properties)) {
            $properties = [$properties];
        }

        $fileProperties = [];
        /** @var FileRefreshedProperty $property */
        foreach ($properties as $property) {
            $filePropertyName = $property->value;
            if (array_key_exists($filePropertyName, $updaters)) {
                $fileProperties[$filePropertyName] = $updaters[$filePropertyName]();
            }
        }

        $isRefreshed = false;
        foreach ($fileProperties as $name => $value) {
            if (! property_exists($this->data, $name)) {
                $isRefreshed = true;
                break;
            }
            if ($value !== $this->data->$name) {
                $isRefreshed = true;
                break;
            }
        }

        if (! $isRefreshed) {
            return $this;
        }
        $data = FileData::from([...$this->data->toArray(), ...$fileProperties]);

        return tap(
            $this->fileLayer()->makeFileWrapper($data),
            fn($file) => Refreshed::dispatch($file)
        );
    }

    /**
     * @param  array<mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists($this->fileLayer(), $name)) {
            return $this->fileLayer()->$name($this, ...$arguments);
        }
        if (property_exists($this->data, $name)) {
            $reflection = new \ReflectionProperty($this->data, $name);
            if ($reflection->isPublic()) {
                return $this->data->$name;
            }
        }
        throw new \BadMethodCallException(sprintf('Method %s does not exist in %s', $name, static::class));
    }

    public function misplaced(): bool
    {
        return $this->fileLayer()->misplaced($this);
    }

    public function sync(): self
    {
        return $this->fileLayer()->sync($this);
    }

    public function working(): self|TmpFileWrapper
    {
        return $this->fileLayer()->working($this);
    }

    public function process(array $actions): self
    {
        return $this->fileLayer()->process($this, $actions);
    }

    public function processTo(array $actions, bool $force = false): self
    {
        return $this->fileLayer()->processTo($this, $actions, null, $force);
    }

    public function laravelFile(): File
    {
        return $this->fileLayer()->laravelFile($this);
    }

    public function isLocal(): bool
    {
        return $this->fileLayer()->isLocal($this);
    }

    public function fullPath(): string
    {
        return $this->fileLayer()->fullPath($this);
    }

    /**
     * @return array<FileRefreshedProperty>
     */
    protected function refreshedProperties(): array
    {
        return FileRefreshedProperty::cases();
    }
}
