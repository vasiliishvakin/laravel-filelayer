<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\Computed;

class DirectoryData extends FileSystemItemData
{
    #[Computed]
    public readonly ?string $name;

    public function __construct(
        ?string $path = null,
        ?string $storage = null,
        public readonly ?Collection $files = null,
        public readonly ?Collection $directories = null,
    ) {
        parent::__construct(path: $path, storage: $storage);

        $this->name = $path ? basename($path) : null;
    }
}
