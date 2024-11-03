<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Carbon\CarbonImmutable;
use Illuminate\Http\File;
use Vaskiq\LaravelFileLayer\Contracts\FileWrapperInterface;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Enums\FileRefreshedProperties;
use Vaskiq\LaravelFileLayer\Facades\Mime;
use Vaskiq\LaravelFileLayer\FileLayer;

/**
 * @method ?string get()
 * @method bool exists()
 * @method bool delete()
 * @method bool misplaced()
 * @method FileWrapper sync()
 * @method static|FileWrapperInterface working()
 * @method static|FileWrapperInterface process(array $actions)
 * @method static|FileWrapperInterface processTo(array $actions)
 * @method File laravelFile()
 * @method bool isLocal()
 * @method string fullPath()
 *
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
     * @param  array<FileRefreshedProperties>|FileRefreshedProperties|null  $properties
     */
    public function refresh(array|FileRefreshedProperties|null $properties = null): self
    {
        $updaters = [
            FileRefreshedProperties::SIZE->value => fn () => $this->fileLayer()->size($this),
            FileRefreshedProperties::LAST_MODIFIED->value => fn () => $this->fileLayer()->lastModified($this),
            FileRefreshedProperties::MIME->value => fn () => $this->fileLayer()->mime($this),
            FileRefreshedProperties::URL->value => fn () => $this->fileLayer()->url($this),
        ];

        $properties = $properties ?? $this->refreshedProperties();
        if (! is_array($properties)) {
            $properties = [$properties];
        }

        $fileProperties = [];
        /** @var FileRefreshedProperties $property */
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

        return $this->fileLayer()->makeFileWrapper($data);
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
        return $this->data()->lastModified ?? $this->fileLayer()->lastModified($this);
    }

    public function mime(): string
    {
        return $this->data()->mimeType ?? $this->fileLayer()->mime($this);
    }

    public function url(): string
    {
        return $this->data()->url ?? $this->fileLayer()->url($this);
    }

    /**
     * @return array<FileRefreshedProperties>
     */
    protected function refreshedProperties(): array
    {
        return FileRefreshedProperties::cases();
    }
}
