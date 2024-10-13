<?php

namespace Vaskiq\LaravelFileLayer\Data;

use Spatie\LaravelData\Data;

class FileData extends Data
{
    public function __construct(
        public int $id,
        public ?string $storage,
        public string $path,
        public ?string $mime,
        public ?int $size,
        public ?string $source_name,
        public ?string $alias = null,
        public ?array $metadata = null,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}
}
