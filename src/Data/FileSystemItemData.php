<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

use Spatie\LaravelData\Data;

class FileSystemItemData extends Data
{
    public function __construct(
        public readonly ?string $path = null,
        public readonly ?string $storage = null,
    ) {}
}
