<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers;

use Illuminate\Support\Str;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Facades\Mime;
use Vaskiq\LaravelFileLayer\StorageManager;
use Vaskiq\LaravelFileLayer\StorageTools\StorageOperator;

class TmpFileWrapper extends FileWrapper
{
    public function __construct(StorageManager $manager, ?string $mime = null, ?string $content = null)
    {
        $mime = $data->mime ?? 'text/plain';
        $extension = Mime::extension($mime);
        $filePath = $this->create($manager, $extension, $content);

        $tmpData = FileData::from([
            'storage' => StorageOperator::TMP_STORAGE_NAME,
            'path' => $filePath,
            'mime' => $mime,
        ]);

        parent::__construct($manager, $tmpData);
    }

    protected function create(StorageManager $manager, string $extension, string $content): string
    {
        $tmpStorage = $manager->getStorageOperator()->tmp();
        do {
            $fileName = Str::ulid().'.'.$extension;
        } while ($tmpStorage->exists($fileName));

        if (! $tmpStorage->put($fileName, $content)) {
            throw new \RuntimeException('Failed to create a temporary file');
        }

        return $fileName;
    }

    public function working(): self
    {
        return $this;
    }
}
