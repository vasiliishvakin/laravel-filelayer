<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Vaskiq\LaravelDataLayer\Data\Casts\JsonToArrayCast;

class FileData extends Data
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $storage,
        public readonly string $path,
        public readonly ?string $mime,
        public readonly ?int $size,

        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?CarbonImmutable $last_modified,

        public readonly ?string $source = null,
        public readonly ?string $alias = null,

        #[WithCast(JsonToArrayCast::class)]
        public readonly ?array $metadata = [],

        public readonly ?string $url = null,

        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?CarbonImmutable $created_at = null,

        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?CarbonImmutable $updated_at = null
    ) {}

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
