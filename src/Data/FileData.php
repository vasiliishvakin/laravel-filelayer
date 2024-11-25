<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Vaskiq\LaravelDataLayer\Data\Casts\JsonToArrayCast;

class FileData extends BaseFileData
{
    public function __construct(

        public readonly ?int $id = null,

        ?string $path = null,
        ?string $storage = null,

        ?string $directory = null,
        ?string $filename = null,

        ?string $mime = null,
        ?int $size = null,

        #[WithCast(DateTimeInterfaceCast::class)]
        ?CarbonImmutable $last_modified = null,

        public readonly ?string $source = null,
        public readonly ?string $alias = null,

        #[WithCast(JsonToArrayCast::class)]
        public readonly ?array $metadata = [],

        ?string $url = null,

        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?CarbonImmutable $created_at = null,

        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?CarbonImmutable $updated_at = null,

        ?PathInfoData $path_info = null,

        ?string $hash = null,
        ?string $hash_name = null,
        ?string $etag = null,

        public readonly ?string $origin = null,
    ) {
        parent::__construct(
            path: $path,
            storage: $storage,
            directory: $directory,
            filename: $filename,
            mime: $mime,
            size: $size,
            last_modified: $last_modified,
            url: $url,
            path_info: $path_info,
            hash: $hash,
            hash_name: $hash_name,
            etag: $etag,
        );
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
