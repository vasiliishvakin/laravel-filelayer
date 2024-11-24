<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as RawBuilder;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Vaskiq\LaravelDataLayer\Contracts\DataFactoryInterface;
use Vaskiq\LaravelDataLayer\Repositories\EloquentRepository;
use Vaskiq\LaravelFileLayer\Data\DirectoryData;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Enums\FileSystemItemType;
use Vaskiq\LaravelFileLayer\Models\File;

/**
 * @extends EloquentRepository<FileData, File>
 */
final class FileRepository extends EloquentRepository
{
    private const DATA_CLASS = FileData::class;

    public function __construct(File $model, DataFactoryInterface $dataFactory)
    {
        parent::__construct($model, $dataFactory);
    }

    public function dataClass(): string
    {
        return self::DATA_CLASS;
    }

    public function findByPath(string $path, ?string $storage = null): ?FileData
    {
        $model = $this->queryByPath($path, $storage)->first();

        return $model ? $this->toData($model) : null;
    }

    public function existsByPath(string $path, ?string $storage = null): bool
    {
        return $this->queryByPath($path, $storage)->exists();
    }

    /**
     * @return Collection<int, string>
     */
    public function pathsInDirectory(?string $directory, ?string $storage = null, ?int $limit = null, ?int $offset = null): Collection
    {
        return $this->queryByDirectory($directory, $storage)
            // @phpstan-ignore argument.type
            ->when($limit, fn ($q) => $q->limit($limit))
            // @phpstan-ignore argument.type
            ->when($offset, fn ($q) => $q->offset($offset))
            ->pluck('path');
    }

    /**
     * @param  Collection<int, string>  $paths
     * @return Collection<int, string>
     */
    public function pathsNotInDirectory(?string $directory, ?string $storage, Collection $paths): Collection
    {
        $inDirectory = $this->queryByDirectory($directory, $storage)
            ->whereIn('path', $paths)
            ->pluck('path');

        return $paths->diff($inDirectory);
    }

    public function countInDirectory(?string $directory, ?string $storage = null): int
    {
        return $this->queryByDirectory($directory, $storage)->count();
    }

    /**
     * @return Collection<int, FileData>
     */
    public function filesInDirectory(?string $directory = null, ?string $storage = null): Collection
    {
        $items = $this->queryByDirectory($directory, $storage)->get();

        return $this->toDataCollection($items);
    }

    /**
     * @return Builder<File>
     */
    public function queryByDirectory(?string $directory = null, array|string|null $storage = null): Builder
    {
        return $this->query()
            ->when($directory, fn (Builder $q) => $q->where('directory', $directory))
            ->when(! $directory, fn (Builder $q) => $q->whereNull('directory'))
            ->when($storage, function (Builder $q, $storage) {
                if (is_array($storage)) {
                    if (! empty($storage)) {
                        $q->whereIn('storage', $storage);
                    }
                } else {
                    $q->where('storage', $storage);
                }
            });
    }

    public function queryRawByDirectory(?string $directory = null, array|string|null $storage = null): RawBuilder
    {
        return $this->raw()
            ->when($directory, fn (RawBuilder $q) => $q->where('directory', $directory))
            ->when(! $directory, fn (RawBuilder $q) => $q->whereNull('directory'))
            ->when($storage, function (RawBuilder $q, $storage) {
                if (is_array($storage)) {
                    if (count($storage) > 0) {
                        $q->whereIn('storage', $storage);
                    }
                } else {
                    $q->where('storage', $storage);
                }
            });
    }

    /**
     * @return Builder<File>
     */
    public function queryByPath(string $path, array|string|null $storage = null): Builder
    {
        return $this->query()
            ->where('path', $path)
            ->when($storage, function (Builder $q, $storage) {
                if (is_array($storage)) {
                    if (count($storage) > 0) {
                        $q->whereIn('storage', $storage);
                    }
                } else {
                    $q->where('storage', $storage);
                }
            });
    }

    public function queryRawByPath(string $path, array|string|null $storage = null): RawBuilder
    {
        return $this->raw()
            ->where('path', $path)
            ->when($storage, function (RawBuilder $q, $storage) {
                if (is_array($storage)) {
                    if (count($storage) > 0) {
                        $q->whereIn('storage', $storage);
                    }
                } else {
                    $q->where('storage', $storage);
                }
            });
    }

    public function url(string $path, ?string $storage = null): ?string
    {
        $result = $this->queryRawByPath($path, $storage)->first('url');

        return $result?->url;
    }

    public function directory(?string $path = null, array|string|null $storage = null, ?FileSystemItemType $type = null): DirectoryData
    {
        $files = null;
        $query = $this->queryByDirectory($path, $storage);
        if ($type !== FileSystemItemType::DIRECTORY) {
            $files = $query->get();
            $files = $this->toDataCollection($files);
        }

        $segmentCount = substr_count($path, '/') + 1;

        $directories = null;
        if ($type !== FileSystemItemType::FILE) {
            $queryDirs = $this->raw()
                ->selectRaw("DISTINCT SUBSTRING_INDEX(directory, '/', ?) AS first_level_dir, storage", [$segmentCount + 1])
                ->where('directory', 'LIKE', $path.'/%')
                ->where('directory', '!=', $path)
                ->when($storage, function (RawBuilder $q, $storage) {
                    if (is_array($storage)) {
                        if (count($storage) > 1) {
                            $q->whereIn('storage', $storage);
                        } elseif (count($storage) === 1) {
                            $q->where('storage', $storage[0]);
                        }
                    } else {
                        $q->where('storage', $storage);
                    }
                });
            $directories = $queryDirs->get('first_level_dir');
            $directories = $directories->map(fn ($dir) => DirectoryData::from([
                'path' => $dir->first_level_dir,
                'storage' => $dir->storage,
            ]));
        }

        $resultStorage = null;
        if (is_array($storage)) {
            if (count($storage) === 1) {
                $resultStorage = $storage[0];
            }
        }

        $result = DirectoryData::from([
            'path' => $path,
            'storage' => $resultStorage,
            'files' => $files,
            'directories' => $directories,
        ]);

        return $result;
    }

    /**
     * @param  FileData  $data
     * @return FileData
     */
    public function save(Data $data): Data
    {
        $keyName = $this->model->getKeyName();
        $fields = $data->toArray();

        $model = $this->query()->where(function ($query) use ($fields, $keyName) {
            $query->when(isset($fields[$keyName]), function ($query) use ($fields, $keyName) {
                $query->where($keyName, $fields[$keyName]);
            });

            $query->when(isset($fields['path']), function ($query) use ($fields) {
                $query->orWhere('path', $fields['path']);
            });
        })->first();

        $model = $model ?? $this->model();

        $model = $this->fillFromArray($model, $fields);

        $model->save();

        return $this->toData($model);
    }
}
