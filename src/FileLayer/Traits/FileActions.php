<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer\Traits;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;
use Stringable;
use Vaskiq\LaravelFileLayer\Contracts\FileWrapperInterface;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Generators\FileName\FileNameGeneratorByActions;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

trait FileActions
{
    use FileInfo;
    use WithFileRepository;
    use WithFileWrappers;
    use WithStorageOperator;

    public function relocate(FileWrapper $file, ?string $storageName = null, array $options = []): FileWrapper
    {
        $fileData = $file->data();

        $storage = $this->storageOperator()->storage($storageName);

        $newPath = $storage->putFileAs(
            path: $file->directory(),
            file: $file->laravelFile(),
            name: $file->name(),
        );

        if (! $newPath) {
            throw new \Exception(sprintf('Failed to put file to storage %s', $storage->name));
        }

        $source = $file->path() !== $newPath ? $file->path() : null;

        $fileData = FileData::from([
            ...$fileData->toArray(),
            'path' => $newPath,
            'storage' => $storage->name,
            'source' => $source,
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

        if ($file->storage() !== $this->storageOperator()->mainStorageName) {
            $file = $this->relocate($file, $this->storageOperator()->mainStorageName);
            $dirty = true;
        }

        if (! $file->id() || $dirty) {
            return $this->register($file);
        }

        return $file;
    }

    public function get(FileWrapper $file): ?string
    {
        $storage = $this->storageOperator()->storage($file->storage());

        return $storage->get($file->path());
    }

    public function delete(FileWrapper $file): bool
    {
        $storage = $this->storageOperator()->storage($file->storage());
        $deletedInStorage = $storage->exists($file->path()) ? $storage->delete($file->path()) : true;

        $deletedInDb = $file->id() !== null ? $this->fileRepository()->delete($file->id()) : true;

        return $deletedInStorage && $deletedInDb;
    }

    public function put(string $path, string $content, ?string $storageName = null): FileWrapper
    {
        $this->fileByPath($path)?->delete();

        $storage = $this->storageOperator()->storage($storageName);

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

        $storageOperator = $this->storageOperator()->storage($newStorage);
        $newDir = dirname($newPath);
        $newFileName ??= basename($newPath);

        $newPath = $storageOperator->putFileAs($newDir, $file->laravelFile(), $newFileName);

        $fileWrapper = $this->makeFileWrapper(FileData::from([
            'path' => $newPath,
            'storage' => $storageOperator->name,
            'source' => $newPath !== $file->path() ? $file->path() : null,
        ]));

        return $this->register($fileWrapper);
    }

    public function working(FileWrapper $file): FileWrapper|FileWrapperInterface
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

    public function process(FileWrapper $file, array $actions): FileWrapper|FileWrapperInterface
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

    public function processTo(
        FileWrapper $file,
        array $actions,
        string|Stringable|callable|null $newPath = null
    ): FileWrapper|FileWrapperInterface {
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

    private function getPipeline(): Pipeline
    {
        return App::make(Pipeline::class);
    }

    private function register(FileWrapper $file, bool $forceRefresh = false): FileWrapper
    {
        if ($forceRefresh || $file->incomplete()) {
            $file->refresh();
        }

        /** @var FileData */
        $fileData = $this->fileRepository()->save($file->data());

        return $this->makeFileWrapper($fileData);
    }

    private function generatePathForActions(FileWrapper $file, array $actions, string|Stringable|callable|null $newPath = null): string
    {
        $newPath = $newPath ?? FileNameGeneratorByActions::class;

        return (string) match (true) {
            $newPath instanceof \Stringable => (string) $newPath,
            is_callable($newPath) => $newPath($file, $actions),
            class_exists($newPath) => (function () use ($newPath, $file, $actions) {
                $pathGenerator = App::make($newPath);
                if (! is_callable($pathGenerator)) {
                    throw new \InvalidArgumentException('Path generator must be callable.');
                }

                return $pathGenerator($file, $actions);
            })(),
            is_string($newPath) => $newPath,
        };
    }
}
