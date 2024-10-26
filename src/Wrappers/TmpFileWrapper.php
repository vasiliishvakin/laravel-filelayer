<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Facades\Mime;
use Vaskiq\LaravelFileLayer\FileLayer;
use Vaskiq\LaravelFileLayer\Storage\StorageOperator;
use Vaskiq\LaravelFileLayer\TmpFileLayer;

class TmpFileWrapper extends FileWrapper
{
    public function __construct(FileLayer $manager, ?string $mime = null, ?string $content = null)
    {
        $mime = $mime ?? 'text/plain';
        $extension = Mime::extension($mime);
        $filePath = $this->create($manager, $extension, $content);

        $tmpData = FileData::from([
            'storage' => StorageOperator::TMP_STORAGE_NAME,
            'path' => $filePath,
            'mime' => $mime,
        ]);

        parent::__construct($tmpData, $manager);
    }

    /** not implemented for tmp file */
    public static function fromData(FileData $data, ?FileLayer $manager = null): self
    {
        throw new \RuntimeException('Cannot create a temporary file from data');
    }

    public static function fromContent(?string $mime = null, ?string $content = null, ?TmpFileLayer $manager = null): self
    {
        $manager ??= App::make(TmpFileLayer::class);

        return new static($manager, $mime, $content);
    }

    public function working(): static
    {
        return $this;
    }

    protected function create(FileLayer $manager, string $extension, ?string $content = null): string
    {
        $content ??= '';
        $tmpStorage = $manager->tmpStorage();
        do {
            $fileName = Str::ulid().'.'.$extension;
        } while ($tmpStorage->exists($fileName));

        if (! $tmpStorage->put($fileName, $content)) {
            throw new \RuntimeException('Failed to create a temporary file');
        }

        return $fileName;
    }
}
