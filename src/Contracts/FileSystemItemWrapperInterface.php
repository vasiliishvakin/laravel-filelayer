<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Contracts;

use Vaskiq\LaravelFileLayer\Data\FileSystemItemData;

interface FileSystemItemWrapperInterface extends FsInfoInterface
{
    public static function from(FileSystemItemData $data, ?FileLayerInterface $fileLayer): static;

    public function data(): FileSystemItemData;

    public function fileLayer(): FileLayerInterface;

    public function storage(): ?string;

    public function path(): ?string;

    public function toKey(): string;

    public function isLocal(): bool;
}
