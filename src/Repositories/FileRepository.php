<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Vaskiq\LaravelDataLayer\Contracts\DataFactoryInterface;
use Vaskiq\LaravelDataLayer\Repositories\EloquentRepository;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Models\File;

/**
 * @method FileData toData(mixed $model)
 */
class FileRepository extends EloquentRepository
{
    protected string $dataClass = FileData::class;

    public function __construct(File $model, DataFactoryInterface $dataFactory)
    {
        parent::__construct($model, $dataFactory);
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

    public function pathsInDirectory(?string $directory, ?string $storage = null, ?int $limit = null, ?int $offset = null): Collection
    {
        return $this->queryByDirectory($directory, $storage)
            ->when($limit, fn ($q) => $q->limit($limit))
            ->when($offset, fn ($q) => $q->offset($offset))
            ->pluck('path');
    }

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

    public function filesInDirectory(?string $directory = null, ?string $storage = null): Collection
    {
        $items = $this->queryByDirectory($directory, $storage)->get();

        return $this->toDataCollection($items);
    }

    private function queryByDirectory(?string $directory = null, ?string $storage = null): Builder
    {
        return $this->query()
            ->when($directory, fn ($q) => $q->where('directory', $directory))
            ->when(! $directory, fn ($q) => $q->whereNull('directory'))
            ->when($storage, fn ($q) => $q->where('storage', $storage));
    }

    private function queryByPath(string $path, ?string $storage = null): Builder
    {
        return $this->query()
            ->where(
                fn ($q) => $q->where('path', $path)
                    ->orWhere('source', $path)
                    ->orWhere('alias', $path)
            )
            ->when($storage, fn ($q) => $q->where('storage', $storage));
    }
}
