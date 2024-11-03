<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

class DirectoryData extends FileSystemItemData
{
    public function __construct(
        ?string $path = null,
        ?string $storage = null,
    ) {
        parent::__construct(path: $path, storage: $storage);
    }
}
