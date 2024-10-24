<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Vaskiq\LaravelFileLayer\Helpers\BinaryDataHelper;
use Vaskiq\LaravelFileLayer\StorageManager;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

class UploadFilesService
{
    public function __construct(
        protected readonly StorageManager $manager,
        protected readonly Request $request
    ) {}

    public function joinPaths(string ...$segments): string
    {
        $normalized = array_map(fn ($segment) => trim($segment, '/'), $segments);

        return implode('/', $normalized);
    }

    public function createFullPath(?string $newPath = null, ?string $fileName = null): string
    {
        $newPath = $newPath ?? '';
        $isDirectory = str_ends_with($newPath, '/');

        if ($isDirectory) {
            if ($fileName === null) {
                throw new \InvalidArgumentException('File name must be provided when newPath is a directory.');
            }

            return rtrim($newPath, '/').'/'.ltrim($fileName, '/');
        }

        $pathInfo = pathinfo($newPath);
        if (isset($pathInfo['extension'])) {
            $newPath = $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'].'/' : '';
        }

        if ($fileName === null) {
            throw new \InvalidArgumentException('File name must be provided.');
        }

        return rtrim($newPath, '/').'/'.ltrim($fileName, '/');
    }

    public function uploadContent(string $path, string $content): FileWrapper
    {
        return $this->manager->put($path, $content);
    }

    public function uploadFileByPath(string $path, ?string $newPath = null, ?string $newFileName = null): FileWrapper
    {
        if (! file_exists($path)) {
            throw new \InvalidArgumentException("File not found at path: $path");
        }

        $newPath ??= str_starts_with($path, base_path())
            ? str_replace(base_path(), '', $path)
            : $path;

        $newFileName ??= basename($newPath);
        $newDir = dirname($newPath) !== '.' ? dirname($newPath) : '';

        $fullNewPath = $this->createFullPath($newDir, $newFileName);

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Failed to read content from: $path");
        }

        return $this->manager->put($fullNewPath, $content);
    }

    public function uploadLaravelUploaded(UploadedFile $file, ?string $newPath = null, ?string $newFileName = null): FileWrapper
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw new \InvalidArgumentException("Unable to access the real path of the uploaded file: {$file->getClientOriginalName()}.");
        }

        $newFileName ??= $file->getClientOriginalName();

        return $this->uploadFileByPath($path, $newPath, $newFileName);
    }

    /**
     * @return FileWrapper|Collection<FileWrapper>
     */
    public function uploadRequestFile(string $fieldName): FileWrapper|Collection
    {
        $files = $this->request->file($fieldName);

        if (! $files) {
            throw new \InvalidArgumentException("No file found for field '{$fieldName}' in the request.");
        }

        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                throw new \InvalidArgumentException("Invalid file uploaded for field '{$fieldName}'.");
            }
        }

        $uploaded = collect($files)->map(fn ($file) => $this->uploadLaravelUploaded($file));

        return $uploaded->count() === 1 ? $uploaded->first() : $uploaded;
    }

    public function uploadBase64File(string $base64, ?string $newPath = null, ?string $newFileName = null): FileWrapper
    {
        $base64Data = BinaryDataHelper::decodeBase64($base64);
        $path = $this->createFullPath($newPath, $newFileName);

        return $this->uploadContent($path, $base64Data->base64);
    }
}
