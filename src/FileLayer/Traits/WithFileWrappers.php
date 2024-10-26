<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer\Traits;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Facades\TmpFile;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

trait WithFileWrappers
{
    private function makeFileWrapper(FileData $fileData): FileWrapper
    {
        return FileWrapper::fromData(data: $fileData, manager: $this);
    }

    private function makeTmpFileWrapper(
        ?string $mime = null,
        ?string $content = null,
    ): TmpFileWrapper {
        return TmpFile::create(content: $content, mime: $mime);
    }
}
