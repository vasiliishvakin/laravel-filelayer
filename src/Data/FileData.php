<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Vaskiq\LaravelDataLayer\Data\Casts\JsonToArrayCast;

class FileData extends FileSystemItemData
{
    public readonly ?string $directory;

    public readonly ?string $filename;

    public function __construct(
        ?string $path = null,
        ?string $storage = null,

        public readonly ?int $id = null,

        ?string $directory = null,
        ?string $filename = null,

        public readonly ?string $mime = null,
        public readonly ?int $size = null,

        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?CarbonImmutable $last_modified = null,

        public readonly ?string $source = null,
        public readonly ?string $alias = null,

        #[WithCast(JsonToArrayCast::class)]
        public readonly ?array $metadata = [],

        public readonly ?string $url = null,

        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?CarbonImmutable $created_at = null,

        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?CarbonImmutable $updated_at = null,

        ?PathInfoData $path_info = null,
    ) {
        parent::__construct(path: $path, storage: $storage);

        $this->directory = $directory ?? $path_info?->directory;
        $this->filename = $filename ?? $path_info?->filename;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        if (empty($data['created_at'])) {
            unset($data['created_at']);
        }
        if (empty($data['updated_at'])) {
            unset($data['updated_at']);
        }

        $data['metadata'] = ! empty($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null;

        return $data;
    }
}
