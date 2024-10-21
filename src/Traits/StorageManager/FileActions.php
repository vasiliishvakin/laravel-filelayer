<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Traits\StorageManager;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;
use Stringable;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Generators\FileName\FileNameGeneratorByActions;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

trait FileActions
{
    use FileInfo;
    use FindFile;

    abstract public function makeTmpFileWrapper(string $mime, ?string $content = null): TmpFileWrapper;

    public function getPipeline(): Pipeline
    {
        return App::make(Pipeline::class);
    }

    public function register(FileWrapper $file, bool $forceRefresh = false): FileWrapper
    {
        if ($forceRefresh || $file->incomplete()) {
            $file->refresh();
        }

        $fileData = $this->getFileRepository()->save($file->data());

        return $this->makeFileWrapper($fileData);
    }

    public function relocate(FileWrapper $file, ?string $storageName = null, $options = []): FileWrapper
    {
        $fileData = $file->data();

        $storage = $this->getStorageOperator()->storage($storageName);

        $sourceName = $file->name();

        $newPath = $storage->putFileAs(
            path: $file->directory(),
            file: $file->laravelFile(),
            name: $file->name(),
        );

        $fileData = FileData::from([
            ...$fileData->toArray(),
            'path' => $newPath,
            'storage' => $storage->name,
            'source_name' => $sourceName,
        ]);

        return $this->makeFileWrapper($fileData);
    }

    public function sync(FileWrapper $file, bool $forceRefresh = false): FileWrapper
    {
        $dirty = false;

        if ($forceRefresh || $file->incomplete()) {
            $file->refresh();
            $dirty = true;
        }

        if ($file->storage() !== $this->getStorageOperator()->mainStorageName) {
            $file = $this->relocate($file, $this->getStorageOperator()->mainStorageName);
            $dirty = true;
        }

        if (! $file->id() || $dirty) {
            return $this->register($file);
        }

        return $file;
    }

    public function exists(FileWrapper $file): bool
    {
        $storage = $this->getStorageOperator()->storage($file->storage());

        return $storage->exists($file->path());
    }

    public function get(FileWrapper $file): ?string
    {
        $storage = $this->getStorageOperator()->storage($file->storage());

        return $storage->get($file->path());
    }

    public function delete(FileWrapper $file): bool
    {
        $storage = $this->getStorageOperator()->storage($file->storage());
        $deletedInStorage = $storage->exists($file->path()) ? $storage->delete($file->path()) : true;

        $deletedInDb = $file->id() !== null ? $this->getFileRepository()->delete($file->id()) : true;

        return $deletedInStorage && $deletedInDb;
    }

    public function put(string $path, string $content, ?string $storageName = null): FileWrapper
    {
        $this->fileByPath($path)?->delete();

        $storage = $this->getStorageOperator()->storage($storageName);

        if (! $storage->put($path, $content)) {
            throw new \Exception(sprintf('Failed to put file to storage %s', $storage->name));
        }

        $file = $this->makeFileWrapper(FileData::from([
            'path' => $path,
            'storage' => $storage->name,
        ]));

        return $this->register($file);
    }

    public function copy(
        FileWrapper $file,
        ?string $newPath = null,
        ?string $newStorage = null,
        ?string $newFileName = null
    ): FileWrapper {
        if (is_null($newPath) && is_null($newStorage)) {
            throw new \InvalidArgumentException('New path or new storage must be provided');
        }

        $newPath = $newPath ?? $file->path();
        $newStorage = $newStorage ?? $file->storage();

        $storageOperator = $this->getStorageOperator()->storage($newStorage);
        $newDir = dirname($newPath);
        $newFileName ??= basename($newPath);

        $newPath = $storageOperator->putFileAs($newDir, $file->laravelFile(), $newFileName);

        $fileWrapper = $this->makeFileWrapper(FileData::from([
            'path' => $newPath,
            'storage' => $storageOperator->name,
            'alias' => $newPath !== $file->path() ? $file->path() : null,
        ]));

        return $this->register($fileWrapper);
    }

    public function working(FileWrapper $file): FileWrapper
    {
        if ($file->isLocal()) {
            return $file;
        }
        $content = $this->get($file);

        return $this->makeTmpFileWrapper($file->mime(), $content);
    }

    public function workingCopy(FileWrapper $file): FileWrapper
    {
        $content = $this->get($file);

        return $this->makeTmpFileWrapper(mime: $file->mime(), content: $content);
    }

    public function pipe(FileWrapper $file, array $actions): FileWrapper
    {

        $workingFile = $this->working($file);

        foreach ($actions as $action) {
            $workingFile = $action($workingFile);
        }

        return $workingFile;
    }

    public function process(FileWrapper $file, array $actions): FileWrapper
    {
        if (empty($actions)) {
            return $file;
        }

        $workingFile = $this->working($file);
        $pipeline = $this->getPipeline();

        return $pipeline->send($workingFile)
            ->through($actions)
            ->thenReturn();
    }

    protected function generatePathForActions(FileWrapper $file, array $actions, string|Stringable|callable|null $newPath = null): string
    {
        $newPath = $newPath ?? FileNameGeneratorByActions::class;

        return (string) match (true) {
            class_exists($newPath) => (new $newPath)($file, $actions),
            is_string($newPath) => $newPath,
            $newPath instanceof \Stringable => $newPath,
            is_callable($newPath) => $newPath($file, $actions),
            default => throw new \Exception(sprintf('Invalid type of new path argument (%s)', gettype($newPath))),
        };
    }

    public function processTo(
        FileWrapper $file,
        array $actions,
        string|Stringable|callable|null $newPath = null
    ): FileWrapper {
        $newPath = $this->generatePathForActions($file, $actions, $newPath);

        if ($existingFile = $this->fileByPath($newPath, $file->storage())) {
            return $existingFile;
        }

        if (empty($actions)) {
            return $this->copy($file, $newPath);
        }

        $workingFile = $this->workingCopy($file);
        $pipeline = $this->getPipeline();

        return $pipeline->send($workingFile)
            ->through($actions)
            ->then(
                fn ($file) => $this->put($newPath, $file->content())
            );
    }
}
