<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;

class BaseFileData extends FileSystemItemData
{
    public readonly ?string $directory;

    public readonly ?string $filename;

    public function __construct(
        ?string $path = null,
        ?string $storage = null,

        ?string $directory = null,
        ?string $filename = null,

        public readonly ?string $mime = null,
        public readonly ?int $size = null,

        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?CarbonImmutable $last_modified = null,

        public readonly ?string $hash = null,
        public readonly ?string $hash_name = null,
        public readonly ?string $etag = null,

        public readonly ?string $url = null,

        ?PathInfoData $path_info = null,
    ) {
        parent::__construct(path: $path, storage: $storage);

        $this->directory = $directory ?? $path_info?->directory;
        $this->filename = $filename ?? $path_info?->filename;
    }
}
