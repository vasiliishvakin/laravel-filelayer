<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer;

use Carbon\CarbonImmutable;
use Illuminate\Http\File;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Stringable;
use Vaskiq\LaravelFileLayer\Contracts\BaseFileWrapperInterface;
use Vaskiq\LaravelFileLayer\Data\DirectoryData;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Data\PathInfoData;
use Vaskiq\LaravelFileLayer\Enums\FileRefreshedProperties;
use Vaskiq\LaravelFileLayer\Exceptions\FileNotFoundException;
use Vaskiq\LaravelFileLayer\Facades\TmpFile;
use Vaskiq\LaravelFileLayer\FileLayer\BaseFileLayer;
use Vaskiq\LaravelFileLayer\Generators\FileName\FileNameGeneratorByActions;
use Vaskiq\LaravelFileLayer\Repositories\FileRepository;
use Vaskiq\LaravelFileLayer\Storage\StorageOperator;
use Vaskiq\LaravelFileLayer\Wrappers\BaseFileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\DirectoryWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\StorageWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\TmpFileWrapper;

/**
 * @extends BaseFileLayer<FileWrapper>
 */
final class FileLayer extends BaseFileLayer
{
    public function __construct(
        StorageOperator $storageOperator,
        private readonly FileRepository $fileRepository,
    ) {
        parent::__construct($storageOperator);
    }

    /**
     * @deprecated
     */
    public function tmpStorage(): StorageWrapper
    {
        return $this->storageOperator()->tmp();
    }

    public function storageByName(?string $name = null): StorageWrapper
    {
        return $this->storageOperator()->storage($name);
    }

    public function fileRepository(): FileRepository
    {
        return $this->fileRepository;
    }

    public function registeredByPath(string $path): bool
    {
        return $this->fileRepository()->existsByPath($path);
    }

    public function makeFileWrapper(FileData $fileData): FileWrapper
    {
        return FileWrapper::from(data: $fileData, fileLayer: $this);
    }

    public function makeDirectoryWrapper(DirectoryData $directoryData): DirectoryWrapper
    {
        return DirectoryWrapper::from(data: $directoryData, fileLayer: $this);
    }

    public function makeTmpFileWrapper(?string $mime = null, ?string $content = null): TmpFileWrapper
    {
        return TmpFile::create(content: $content, mime: $mime);
    }

    public function tmpFile(
        ?string $content = null,
        ?string $mime = null,
    ): TmpFileWrapper {
        return TmpFile::create(content: $content, mime: $mime);
    }

    public function file(int $id): FileWrapper
    {
        $fileData = $this->fileRepository()->findOrFail($id);

        if ($fileData->storage) {
            if (! $this->storageOperator()->storage($fileData->storage)->exists($fileData->path)) {
                throw new FileNotFoundException(sprintf('File with id %d not found in database', $id));
            }

            return $this->makeFileWrapper($fileData);
        }

        foreach ($this->storageOperator()->storages() as $storageName => $storage) {
            if ($storage->exists($fileData->path)) {
                $fileDataWithStorage = FileData::from([
                    ...$fileData->toArray(),
                    'storage' => $storageName,
                ]);

                return $this->makeFileWrapper($fileDataWithStorage);
            }
        }

        throw new FileNotFoundException(sprintf('File with id %d not found in storage', $id));
    }

    public function fileByPath(string $path, ?string $storageName = null): ?FileWrapper
    {
        $fileData = $this->fileRepository()->findByPath($path, $storageName);
        if ($fileData?->storage) {
            if (! is_null($storageName) && $fileData->storage !== $storageName) {
                return null;
            }

            return $this->makeFileWrapper($fileData);
        }

        foreach ($this->storageOperator()->storages() as $storage) {
            if ($storage->name === StorageOperator::TMP_STORAGE_NAME) {
                continue;
            }
            if ($storage->exists($path)) {
                if ($this->storageOperator()->isLocal($storage)) {
                    $fullPath = $storage->path($path);
                    $isDirectory = is_dir($fullPath);

                    $pathInfoData = PathInfoData::from([
                        'path' => $path,
                        'isDirectory' => $isDirectory,
                    ]);
                }

                $fileData = FileData::from([
                    'path' => $path,
                    'storage' => $storage->name,
                    'path_info' => $pathInfoData ?? null,
                ]);

                return $this->makeFileWrapper($fileData);
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (count($arguments) === 1 && $arguments[0] instanceof BaseFileWrapper) {
            $file = $arguments[0];
            $storage = $this->storageByFile($file);
            if (method_exists($storage, $name)) {
                $path = $this->path($file);

                return $storage->$name($path);
            }
        }
        throw new \BadMethodCallException(sprintf('Method %s not found in %s', $name, self::class));
    }

    public function size(FileWrapper $file): int
    {
        return $this->storageByFile($file)->size($this->path($file));
    }

    public function mime(FileWrapper $file): string|false
    {
        return $this->storageByFile($file)->mimeType($this->path($file));
    }

    public function url(FileWrapper $file): string
    {
        return $this->storageByFile($file)->url($this->path($file));
    }

    public function lastModified(FileWrapper $file): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp(
            $this->storageByFile($file)->lastModified($this->path($file))
        );
    }

    public function get(FileWrapper $file): string
    {
        return $this->storageByFile($file)->get($this->path($file));
    }

    public function laravelFile(FileWrapper $file): File
    {
        $storage = $this->storageByFile($file);
        if (! $this->storageOperator()->isLocal($storage)) {
            throw new \Exception('Storage is not local');
        }

        return new File($storage->path($this->path($file)));
    }

    public function existsPath(string $path, string|StorageWrapper|null $storage = null): bool
    {
        $storage = $storage instanceof StorageWrapper
            ? $storage
            : $this->storageByName($storage);

        return $storage->exists($path);
    }

    public function misplaced(FileWrapper $file): bool
    {
        return $file->storage() !== $this->storageOperator()->mainStorageName;
    }

    public function delete(FileWrapper $file): bool
    {
        if (! $this->exists($file)) {
            return true;
        }

        $storage = $this->storageByFile($file);
        $deletedInStorage = $storage->delete($this->path($file));

        $deletedInDb = $file->repositoryId() !== null ? $this->fileRepository()->delete($file->repositoryId()) : true;

        return $deletedInStorage && $deletedInDb;
    }

    public function put(string $path, string $content, ?string $storageName = null): FileWrapper
    {
        $file = $this->fileByPath($path);
        if ($file) {
            $this->delete($file);
        }

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

    public function working(FileWrapper $file): BaseFileWrapperInterface
    {
        if ($this->isLocal($file)) {
            return $file;
        }
        $content = $this->get($file);

        return $this->makeTmpFileWrapper($file->mime(), $content);
    }

    public function workingCopy(FileWrapper $file): FileWrapper|TmpFileWrapper
    {
        $content = $this->get($file);

        return $this->makeTmpFileWrapper(mime: $file->mime(), content: $content);
    }

    /**
     * @param  array<mixed>  $actions
     */
    public function process(FileWrapper $file, array $actions): FileWrapper
    {
        if (empty($actions)) {
            return $file;
        }

        $workingFile = $this->working($file);
        $pipeline = $this->getPipeline();

        $pipeline->send($workingFile)
            ->through($actions)
            ->thenReturn();

        return $file;
    }

    /**
     * @param  array<mixed>  $actions
     */
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
                fn ($file) => $this->put($newPath, $this->get($workingFile))
            );
    }

    /**
     * @return Collection<int, FileWrapper>
     */
    public function files(DirectoryWrapper $directory): Collection
    {
        $storage = $this->storageByFile($directory);
        $files = $storage->files($this->path($directory));

        if (empty($files)) {
            return collect();
        }

        $filesData = $this->fileRepository()
            ->filesInDirectory($this->path($directory), $storage->name)
            ->keyBy('path');

        return collect($files)->map(function ($filePath) use ($filesData, $storage) {
            $fileData = $filesData->get($filePath) ?? FileData::from([
                'path' => $filePath,
                'storage' => $storage->name,
            ]);

            return $this->makeFileWrapper($fileData);
        });
    }

    /**
     * @return Collection<int, DirectoryWrapper>
     */
    public function directories(DirectoryWrapper $directory): Collection
    {
        $storage = $this->storageByFile($directory);
        $directories = $storage->directories($this->path($directory));

        if (empty($directories)) {
            return collect();
        }

        return collect($directories)->map(function ($directoryPath) use ($storage) {
            $directoryData = DirectoryData::from([
                'path' => $directoryPath,
                'storage' => $storage->name,
            ]);

            return $this->makeDirectoryWrapper($directoryData);
        });
    }

    public function relocate(FileWrapper $file, ?string $storageName = null, array $options = []): FileWrapper
    {
        $fileData = $file->data();

        $storage = $this->storageOperator()->storage($storageName);

        if (! $this->existsPath($this->path($file), $storage)) {
            $newPath = $storage->putFileAs(
                path: $file->directory(),
                file: $this->laravelFile($file),
                name: $file->name(),
            );

            if (! $newPath) {
                throw new \Exception(sprintf('Failed to put file to storage %s', $storage->name));
            }

            $source = $file->path() !== $newPath ? $file->path() : null;
        }

        $fileData = FileData::from([
            ...$fileData->toArray(),
            'path' => isset($newPath) ? $newPath : $fileData->path,
            'storage' => $storage->name,
            'source' => isset($source) ? $source : null,
        ]);

        return $this->makeFileWrapper($fileData);
    }

    public function sync(FileWrapper $file, bool $forceRefresh = false): FileWrapper
    {
        $dirty = false;

        if ($forceRefresh || $file->incomplete()) {
            $file = $file->refresh();
            $dirty = true;
        }

        if ($this->misplaced($file)) {
            $file = $this->relocate($file);
            $file = $file->refresh(FileRefreshedProperties::URL);
            $dirty = true;
        }

        if (! $file->repositoryId() || $dirty) {
            return $this->register($file);
        }

        return $file;
    }

    public function directory(string $path, string|StorageWrapper|null $storage = null): DirectoryWrapper
    {
        $storage = $storage instanceof StorageWrapper
            ? $storage
            : $this->storageByName($storage);

        $directoryData = DirectoryData::from([
            'path' => $path,
            'storage' => $storage->name,
        ]);

        return $this->makeDirectoryWrapper($directoryData);
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

    /**
     * @param  array<mixed>  $actions
     */
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
