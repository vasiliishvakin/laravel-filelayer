<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer\Traits;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Facades\TmpFileLayer;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

trait WithFileWrappers
{
    public function makeFileWrapper(FileData $fileData): FileWrapper
    {
        return FileWrapper::fromData(data: $fileData, manager: $this);
    }

    public function makeTmpFileWrapper(
        ?string $mime = null,
        ?string $content = null,
    ): TmpFileWrapper {
        return TmpFileLayer::create(content: $content, mime: $mime);
    }
}
