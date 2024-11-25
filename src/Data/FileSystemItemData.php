<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

use Spatie\LaravelData\Data;
use Symfony\Component\Filesystem\Path;

class FileSystemItemData extends Data
{
    public readonly ?string $path;

    public function __construct(
        ?string $path = null,
        public readonly ?string $storage = null,
    ) {
        $this->path = Path::canonicalize($path);
    }
}
