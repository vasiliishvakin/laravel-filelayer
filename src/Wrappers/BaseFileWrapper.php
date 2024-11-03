<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Vaskiq\FileLayer\Contracts\FileLayerInterface;
use Vaskiq\LaravelFileLayer\Contracts\BaseFileWrapperInterface;
use Vaskiq\LaravelFileLayer\Data\FileSystemItemData;

/**
 * @template TData of FileSystemItemData
 * @template TFileLayer of FileLayerInterface
 *
 * @extends FileSystemItemWrapper<TData, TFileLayer>
 */
class BaseFileWrapper extends FileSystemItemWrapper implements BaseFileWrapperInterface {}
