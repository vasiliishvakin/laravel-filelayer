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

    public function findByPath(string $path): ?FileData
    {
        $model = $this->query()->where('path', $path)
            ->orWhere('alias', $path)
            ->first();

        return $model ? $this->toData($model) : null;
    }
}
