<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer;

use Closure;
use Illuminate\Http\File;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Stringable;
use Vaskiq\LaravelFileLayer\Data\DirectoryData;
use Vaskiq\LaravelFileLayer\Data\FileData;
use Vaskiq\LaravelFileLayer\Enums\FileRefreshedProperty;
use Vaskiq\LaravelFileLayer\Enums\FileSystemItemType;
use Vaskiq\LaravelFileLayer\Events\Copied;
use Vaskiq\LaravelFileLayer\Events\Deleted;
use Vaskiq\LaravelFileLayer\Events\Finding;
use Vaskiq\LaravelFileLayer\Events\Founded;
use Vaskiq\LaravelFileLayer\Events\Processed;
use Vaskiq\LaravelFileLayer\Events\Registered;
use Vaskiq\LaravelFileLayer\Events\Relocated;
use Vaskiq\LaravelFileLayer\Events\Stored;
use Vaskiq\LaravelFileLayer\Events\Synced;
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
    private readonly bool $relationEnabled;

    public function __construct(
        StorageOperator $storageOperator,
        private readonly FileRepository $fileRepository,
    ) {
        parent::__construct($storageOperator);
        $this->relationEnabled = config('filelayer.relocation.enabled', false);
    }

    /**
     * @deprecated
     */
    public function tmpStorage(): StorageWrapper
    {
        return $this->storageOperator()->tmp();
    }

    public function fileRepository(): FileRepository
    {
        return $this->fileRepository;
    }

    public function registeredByPath(string $path): bool
    {
        $path = $this->normalizePath($path);

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

    public function makeTmpFileWrapper(?string $mime = null, ?string $content = null, bool $lazyDelete = false): TmpFileWrapper
    {
        return TmpFile::create(content: $content, mime: $mime, lazyDelete: $lazyDelete);
    }

    public function tmpFile(
        ?string $content = null,
        ?string $mime = null,
    ): TmpFileWrapper {
        return TmpFile::create(content: $content, mime: $mime);
    }

    public function file(int $id): FileWrapper
    {
        Finding::dispatch($id);

        $fileData = $this->fileRepository()->findOrFail($id);

        if ($fileData->storage) {
            if (! $this->storageOperator()->storage($fileData->storage)->exists($fileData->path)) {
                throw new FileNotFoundException(sprintf('File with id %d not found in database', $id));
            }

            return tap(
                $this->makeFileWrapper($fileData),
                fn ($file) => Founded::dispatch($file)
            );
        }

        foreach ($this->storageOperator()->storages() as $storageName => $storage) {
            if ($storage->exists($fileData->path)) {
                $fileDataWithStorage = FileData::from([
                    ...$fileData->toArray(),
                    'storage' => $storageName,
                ]);

                return tap(
                    $this->makeFileWrapper($fileDataWithStorage),
                    fn ($file) => Founded::dispatch($file)
                );
            }
        }

        throw new FileNotFoundException(sprintf('File with id %d not found in storage', $id));
    }

    public function fileByPath(string $path, ?string $storageName = null, bool $register = false): ?FileWrapper
    {
        $path = $this->normalizePath($path);

        if ($path === '' || $path === '/') {
            return null;
        }

        Finding::dispatch(['path' => $path, 'storage' => $storageName]);

        $fileData = $this->fileRepository()->findByPath($path, $storageName);

        if ($fileData?->storage) {
            if (! is_null($storageName) && $fileData->storage !== $storageName) {
                return null;
            }
            $file = $this->makeFileWrapper($fileData);
            Founded::dispatch($file);

            return $file;
        }

        return $this->fileByPathFromStorages($path, $storageName, $register);
    }

    public function fileByPathFromStorages(string $path, string|array|StorageWrapper|null $storageName = null, bool $register = false): ?FileWrapper
    {
        $path = $this->normalizePath($path);

        if ($path === '' || $path === '/') {
            return null;
        }

        // $storages = is_string($storageName) ? [$this->selectStorage($storageName)] : $this->storageOperator()->storages();
        $storages = match (true) {
            is_string($storageName) => [$this->selectStorage($storageName)],
            $storageName instanceof StorageWrapper => [$storageName],
            is_null($storageName) => $this->storageOperator()->storages(),
            is_array($storageName) => array_map(fn ($storage) => $this->selectStorage($storage), $storageName),
            default => throw new \InvalidArgumentException('Storage must be string, StorageWrapper, or array of strings or StorageWrappers'),
        };

        foreach ($storages as $storage) {
            if ($storage->name === StorageOperator::TMP_STORAGE_NAME) {
                continue;
            }

            if ($this->existsPath($path, $storage)) {
                $pathInfoData = $this->pathInfo($path, $storage);

                $fileData = FileData::from([
                    'path' => $path,
                    'storage' => $storage->name,
                    'path_info' => $pathInfoData,
                ]);

                $file = $this->makeFileWrapper($fileData);

                if ($register) {
                    return tap(
                        $this->register($file),
                        fn ($file) => Founded::dispatch($file)
                    );
                }

                return $file;
            }
        }

        return null;
    }

    /**
     * @return Collection<FileWrapper>
     */
    public function filesByPaths(array $paths, ?string $storageName = null, bool $register = false): Collection
    {
        $paths = array_map(fn ($path) => $this->normalizePath($path), $paths);

        $filesData = $this->fileRepository()->findByArrayPaths($paths, $storageName);

        $files = $filesData->map(fn ($fileData) => $this->makeFileWrapper($fileData));

        $unfoundedPaths = array_diff($paths, $filesData->pluck('path')->toArray());
        foreach ($unfoundedPaths as $path) {
            $file = $this->fileByPathFromStorages($path, $storageName, $register);
            if ($file) {
                if ($register) {
                    $file = $this->register($file);
                }
                $files->push($file);
            }
        }

        return $files;
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

    public function misplaced(FileWrapper $file): bool
    {
        if ($file->storage() !== $this->storageOperator()->mainStorageName) {
            return true;
        }

        if (! $this->exists($file, $this->storageOperator()->mainStorage())) {
            return true;
        }

        if (! $file->etag()) {
            return true;
        }

        return ! $this->checkFileEtagInStorage($file, $this->storageOperator()->mainStorage());
    }

    public function delete(BaseFileWrapper $file): bool
    {
        return $this->deleteByPath($this->path($file));
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

        $newPath = $newPath ? $this->normalizePath($newPath) : $file->path();
        $newStorage = $newStorage ?? $file->storage();

        $storageOperator = $this->storageOperator()->storage($newStorage);
        $newDir = dirname($newPath);
        $newFileName ??= basename($newPath);

        $newPath = $storageOperator->putFileAs($newDir, $file->laravelFile(), $newFileName);

        $pathInfoData = $this->pathInfo($newPath, $newStorage);

        $fileWrapper = $this->makeFileWrapper(FileData::from([
            'path' => $newPath,
            'storage' => $storageOperator->name,
            'source' => $newPath !== $file->path() ? $file->path() : null,
            'path_info' => $pathInfoData,
        ]));

        return tap(
            $this->register($fileWrapper),
            fn ($file) => Copied::dispatch($file)
        );
    }

    public function working(FileWrapper $file): FileWrapper|TmpFileWrapper
    {
        if ($this->isLocal($file)) {
            return $file;
        }
        $content = $this->get($file);

        return $this->makeTmpFileWrapper($file->mime(), $content);
    }

    public function workingCopy(FileWrapper $file, bool $lazyDelete = false): FileWrapper|TmpFileWrapper
    {
        $content = $this->get($file);

        return $this->makeTmpFileWrapper(mime: $file->mime(), content: $content, lazyDelete: $lazyDelete);
    }

    public function workingCopyOn(FileWrapper $file, string $path, string|StorageWrapper $storage): FileWrapper
    {
        $storage = $this->selectStorage($storage);
        if (! $this->storageOperator()->isLocal($storage)) {
            throw new \InvalidArgumentException('Storage must be local');
        }

        $path = $this->normalizePath($path);
        $content = $this->get($file);

        $result = $this->putToStorage($path, $content, $storage);
        if (! $result) {
            throw new \Exception(sprintf('Failed to put file to storage %s', $storage->name));
        }

        $pathInfoData = $this->pathInfo($path, $storage);

        $file = $this->makeFileWrapper(FileData::from([
            'path' => $path,
            'storage' => $storage->name,
            'path_info' => $pathInfoData,
        ]));

        return $file;
    }

    public function put(string $path, string $content, ?string $storageName = null, ?string $origin = null): FileWrapper
    {
        $path = $this->normalizePath($path);

        $file = $this->fileByPath($path, $storageName);
        if ($file) {
            $this->delete($file);
        }

        $storage = $this->storageOperator()->storage($storageName);

        if (! $this->putToStorage($path, $content, $storage)) {
            throw new \Exception(sprintf('Failed to put file to storage %s', $storage->name));
        }

        $pathInfoData = $this->pathInfo($path, $storage);

        $file = $this->makeFileWrapper(FileData::from([
            'path' => $path,
            'storage' => $storage->name,
            'path_info' => $pathInfoData,
            'origin' => $origin,
        ]));

        return tap(
            $this->register($file),
            fn ($file) => Stored::dispatch($file)
        );
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

        return tap(
            $file,
            fn ($file) => Processed::dispatch(['file' => $file, 'newFile' => $file, 'actions' => $actions])
        );
    }

    /**
     * @param  array<mixed>|string  $actions
     */
    public function processTo(
        FileWrapper $file,
        array|string $actions,
        string|Stringable|callable|null $newPath = null,
        bool $force = false,
        bool $defer = false,
    ): FileWrapper {
        $originPath = $file->path();

        $newPath = $this->generatePathForActions($file, $actions, $newPath);
        $newPath = $this->normalizePath($newPath);

        if (! $force && $existingFile = $this->fileByPath(path: $newPath, register: false)) {
            if ($existingFile->registered()) {
                return $existingFile;
            } else {
                $this->delete($existingFile);
            }
        }

        if (empty($actions)) {
            if ($newPath && $newPath !== $originPath) {
                return $this->copy($file, $newPath);
            }

            return $file;
        }

        if (! is_array($actions)) {
            $actions = [$actions];
        }

        $workingFile = $this->workingCopy($file);
        $pipeline = $this->getPipeline();

        return tap(
            $pipeline->send($workingFile)
                ->through($actions)
                ->then(
                    fn ($file) => $this->put(path: $newPath, content: $this->get($file), origin: $originPath)
                ),
            fn ($newFile) => Processed::dispatch(['file' => $file, 'newFile' => $newFile, 'actions' => $actions])
        );
    }

    public function lazyProcessTo(
        FileWrapper $file,
        array|string $actions,
        string|Stringable|callable|null $newPath = null,
        bool $force = false,
        string|StorageWrapper|null $tmpStorage = null,
        ?Closure $callback = null,
    ): FileWrapper|TmpFileWrapper {
        $originPath = $file->path();

        $newPath = $this->generatePathForActions($file, $actions, $newPath);
        $newPath = $this->normalizePath($newPath);

        if (! $force && $existingFile = $this->fileByPath(path: $newPath, register: false)) {
            if ($existingFile->registered()) {
                // return $existingFile;
            } else {
                $this->delete($existingFile);
            }
        }

        if (empty($actions)) {
            if ($newPath && $newPath !== $originPath) {
                defer(fn () => $this->copy($file, $newPath));
            }

            return $file;
        }

        if (! is_array($actions)) {
            $actions = [$actions];
        }

        $tmpStorage = $tmpStorage
            ? $this->selectStorage($tmpStorage)
            : null;

        $workingFile = $tmpStorage
            ? $this->workingCopyOn($file, $newPath, $tmpStorage)
            : $this->workingCopy($file, true);

        $pipeline = $this->getPipeline();

        $resultFile = $pipeline->send($workingFile)
            ->through($actions)
            ->thenReturn();

        $content = $this->get($resultFile);
        defer(function () use ($content, $newPath, $file, $actions, $originPath, $callback) {
            $newFile = $this->put(path: $newPath, content: $content, origin: $originPath);
            if (is_callable($callback)) {
                $callback($newFile);
            }
            Processed::dispatch(['file' => $file, 'newFile' => $newFile, 'actions' => $actions]);
        });

        return $resultFile;
    }

    /**
     * @param  array<array|string|StorageWrapper>  $storage
     */
    public function rawStorageFiles(string $path, array|string|StorageWrapper|null $storage = null): Collection
    {
        return $this->rawStorageItemsByType($path, $storage, FileSystemItemType::FILE);
    }

    /**
     * @param  array<array|string|StorageWrapper>  $storage
     */
    public function rawStorageDirectories(string $path, array|string|StorageWrapper|null $storage = null): Collection
    {
        return $this->rawStorageItemsByType($path, $storage, FileSystemItemType::DIRECTORY);
    }

    public function repositoryDirectory(
        string $path,
        array|string|StorageWrapper|null $storage = null,
        ?FileSystemItemType $type = null,
    ): DirectoryData {
        $path = $this->normalizePath($path);

        $storages = is_null($storage) ? null : $this->selectStorages($storage);

        $storageNames = is_null($storage) ? null : array_map(fn ($storage) => $storage->name, $storages);

        return $this->fileRepository()->directory($path, $storageNames, $type);
    }

    public function relocate(FileWrapper $file, ?string $storageName = null, array $options = []): FileWrapper
    {
        if (! $this->relationEnabled) {
            return $file;
        }

        $storage = $this->selectStorage($storageName);

        if ($file->storage() === $storage->name) {
            return $file;
        }

        $fileData = $file->data();
        // $path = $this->path($file);

        // $relocateExistedFile = false;

        // if ($this->existsPath($path, $storage) && $this->checkFileEtagInStorage($file, $storage)) {
        //     $storage = $this->etag($file);
        // }

        if (! $this->existsPath($this->path($file), $storage) || ! $this->checkFileEtagInStorage($file, $storage)) {
            try {
                if ($file->isLocal()) {
                    $newPath = $storage->putFileAs(
                        path: $file->directory(),
                        file: $this->laravelFile($file),
                        name: $file->name(),
                    );
                    if (! $newPath) {
                        throw new \Exception(sprintf('Failed to put file to storage %s', $storage->name));
                    }
                } else {
                    $result = $this->putToStorage($file->path(), $file->get(), $storageName);
                    if (! $result) {
                        throw new \Exception(sprintf('Failed to put file to storage %s', $storage->name));
                    }
                    $newPath = $file->path();
                }
            } catch (\Exception $e) {
                Log::error(sprintf(
                    'Error (%s) on relocate(put) file "%s" to storage "%s": "%s"',
                    class_basename($e),
                    $file->path(),
                    $storage->name,
                    $e->getMessage()
                ), filelayer_log_context());

                return $file; // return original file if failed to put file to new storage
            }
            $source = $file->path() !== $newPath ? $file->path() : null;
        }

        $fileData = FileData::from([
            ...$fileData->toArray(),
            'path' => isset($newPath) ? $newPath : $fileData->path,
            'storage' => $storage->name,
            'source' => isset($source) ? $source : null,
        ]);

        $file = $this->makeFileWrapper($fileData);

        $file = $file->incomplete() ? $file->refresh() : $file->refresh(FileRefreshedProperty::URL);

        Relocated::dispatch($file);

        return $file;
    }

    public function sync(FileWrapper $file, bool $forceRefresh = false): FileWrapper
    {
        $dirty = false;

        if ($forceRefresh || $file->incomplete()) {
            $file = $file->refresh();
            $dirty = true;
        }

        if ($this->misplaced($file)) {
            $currentStorage = $this->storageByFile($file);
            $file = $this->relocate($file);
            $dirty = $dirty ?: $this->storageByFile($file)->name !== $currentStorage->name;
        }

        if (! $file->repositoryId() || $dirty) {
            return tap(
                $this->register($file),
                fn ($file) => Synced::dispatch($file)
            );
        }

        Synced::dispatch($file);

        return $file;
    }

    public function directory(string $path, array|string|StorageWrapper|null $storage = null): ?DirectoryWrapper
    {
        $path = $this->normalizePath($path);

        $storages = $this->selectStorages($storage);

        $rawFiles = $this->rawStorageFiles($path, $storages)
            ->when(count($storages) > 1, function (Collection $files) {
                return $files->sortBy(fn ($file) => $file['storage'])
                    ->unique(fn ($file) => $file['path']);
            })
            ->values();
        $rawDirectories = $this->rawStorageDirectories($path, $storages);

        $rpDirectory = $this->repositoryDirectory($path, $storage, FileSystemItemType::FILE);

        $rpFiles = $rpDirectory->files ?? collect();
        $rpFilesKeys = $rpFiles->keyBy(fn ($file) => $file->path);

        $files = $rawFiles->map(function ($file) use ($rpFilesKeys) {
            $currPath = $file['path'];
            $currStorage = $file['storage'];
            $rpData = $rpFilesKeys[$currPath] ?? null;
            $fileData = $rpData && $rpData->storage === $currStorage
                ? $rpData
                : FileData::from([
                    'path' => $currPath,
                    'storage' => $currStorage,
                ]);

            return $this->makeFileWrapper($fileData);
        });

        $directories = $rawDirectories->map(function ($directory) {
            $directoryData = DirectoryData::from([
                'path' => $directory['path'],
                'storage' => $directory['storage'],
            ]);

            return $this->makeDirectoryWrapper($directoryData);
        });

        $resultStorage = is_null($storage)
            ? null
            : (
                ! is_array($storage)
                ? $storage->name
                : (
                    count($storage) === 1
                    ? $storage[0]->name
                    : null
                )
            );

        $directory = $this->makeDirectoryWrapper(DirectoryData::from([
            'path' => $path,
            'storage' => $resultStorage,
            'files' => $files,
            'directories' => $directories,
        ]));

        return $directory;
    }

    public function deleteByPath(string $path, ?string $storage = null): bool
    {
        $path = $this->normalizePath($path);

        $this->fileRepository()->deleteByPath($path, $storage);

        $storages = $storage
            ? [$this->selectStorage($storage)]
            : $this->storageOperator()->storages();

        foreach ($storages as $storage) {
            if (! $this->existsPath($path, $storage)) {
                continue;
            }
            $storage->delete($path);
        }

        Deleted::dispatch($path);

        return true;
    }

    public function clearProcessed(string|FileWrapper $path): void
    {
        if ($path instanceof FileWrapper) {
            $path = $path->path();
        }

        $path = $this->normalizePath($path);

        $paths = $this->fileRepository()->findPathsByOrigin($path);

        $this->fileRepository()->deleteByOrigin($path);

        foreach ($paths as $path) {
            foreach ($this->storageOperator()->storages() as $storage) {
                if ($storage->exists($path)) {
                    $storage->delete($path);
                }
            }
        }
    }

    /**
     * @param  array<mixed>  $actions
     */
    public function generatePathForActions(FileWrapper $file, array|string $actions, string|Stringable|callable|null $newPath = null): string
    {
        $newPath = $newPath ?? FileNameGeneratorByActions::class;
        if (! is_array($actions)) {
            $actions = [$actions];
        }

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

    private function selectStorages(array|string|StorageWrapper|null $storages): array
    {
        $storages = is_array($storages) ? $storages : [$storages];
        $storages = array_map(fn ($storage) => $this->selectStorage($storage), $storages);

        return $storages;
    }

    /**
     * @param  array<array|string|StorageWrapper>  $storage
     * @return \Illuminate\Support\Collection<int, array{
     *     path: string,
     *     storage: string,
     *     type: FileSystemItemType
     * }>
     */
    private function rawStorageItemsByType(
        string $path,
        array|string|StorageWrapper|null $storage = null,
        FileSystemItemType $type = FileSystemItemType::FILE,
    ): Collection {
        $path = $this->normalizePath($path);

        $storages = $this->selectStorages($storage);

        $items = [];
        foreach ($storages as $storage) {
            if ($this->existsPath($path, $storage)) {
                $currentItems = match ($type) {
                    FileSystemItemType::FILE => $storage->files($path),
                    FileSystemItemType::DIRECTORY => $storage->directories($path),
                };
                $items += array_fill_keys($currentItems, $storage->name);
            }
        }
        if (empty($items)) {
            return collect();
        }

        $itemsData = [];
        foreach ($items as $filePath => $storageName) {
            $itemData = [
                'path' => (string) $filePath,
                'storage' => (string) $storageName,
                'type' => FileSystemItemType::FILE,
            ];

            $itemsData[] = $itemData;
        }

        return collect($itemsData);
    }

    private function getPipeline(): Pipeline
    {
        return App::make(Pipeline::class);
    }

    private function registerFirst(FileWrapper $file): FileWrapper
    {
        if ($file->repositoryId() && ! $file->incomplete()) {
            return $file;
        }

        return $this->register($file);
    }

    private function register(FileWrapper $file, bool $forceRefresh = false): FileWrapper
    {
        if ($forceRefresh || $file->incomplete()) {
            $file = $file->refresh();
        }

        /** @var FileData */
        $fileData = $this->fileRepository()->save($file->data());

        return tap(
            $this->makeFileWrapper($fileData),
            fn ($file) => Registered::dispatch($file)
        );
    }
}
