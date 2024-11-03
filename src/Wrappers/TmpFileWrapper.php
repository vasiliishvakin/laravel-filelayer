<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\TmpFileLayer;

/**
 * @extends BaseFileWrapper<FileData, TmpFileLayer>
 */
class TmpFileWrapper extends BaseFileWrapper
{
    public function working(): self
    {
        return $this;
    }
}
