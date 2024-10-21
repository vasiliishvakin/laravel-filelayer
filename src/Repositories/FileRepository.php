<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Repositories;

use Vaskiq\LaravelDataLayer\Contracts\DataFactoryInterface;
use Vaskiq\LaravelDataLayer\Repositories\EloquentRepository;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Models\File;

class FileRepository extends EloquentRepository
{
    protected string $dataClass = FileData::class;

    public function __construct(File $model, DataFactoryInterface $dataFactory)
    {
        parent::__construct($model, $dataFactory);
    }

    public function findByPath(string $path, ?string $storage = null): ?FileData
    {
        $query = $this->query()->where(function ($query) use ($path) {
            $query->where('path', $path)
                ->orWhere('alias', $path);
        });

        if ($storage) {
            $query->where('storage', $storage);
        }
        $model = $query->first();

        return $model ? $this->toData($model) : null;
    }
}
