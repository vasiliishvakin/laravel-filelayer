<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Generators\FileName;

use Illuminate\Support\Str;
use Symfony\Component\Filesystem\Path;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

class FileNameGeneratorByActions
{
    private readonly string $prefix;
    // private readonly string $noActionsName;

    private readonly int $folder1Length;

    private readonly int $folder2Length;

    private readonly array $reducingClasses;

    public function __construct()
    {
        $this->prefix = self::prefix();
        // $this->noActionsName = config('filelayer.file_name_generator.no_actions_name', 'original');
        $this->folder1Length = config('filelayer.file_name_generator.folder_1_length', 1);
        $this->folder2Length = config('filelayer.file_name_generator.folder_2_length', 1);
        $this->reducingClasses = config('filelayer.file_name_generator.reducing_classes', []);
    }

    public static function prefix(): string
    {
        return config('filelayer.file_name_generator.prefix', 'processed');
    }

    public static function hashAlgorithm(): string
    {
        return config('filelayer.file_name_generator.hash_algorithm', 'sha1');
    }

    public static function hashFileName(string|FileWrapper $file): string
    {
        $name = $file instanceof FileWrapper ? $file->name() : basename($file);
        $extension = $file instanceof FileWrapper ? $file->extension() : Path::getExtension($file, true);
        $hash = hash(self::hashAlgorithm(), $name);

        return $hash . '.' . $extension;
    }

    public static function hashPath(string|FileWrapper $file): string
    {
        $path = $file instanceof FileWrapper ? $file->path() : $file;
        $path = storage_path_normalize($path);
        $extension = $file instanceof FileWrapper ? $file->extension() : Path::getExtension($file, true);
        $hash = hash(self::hashAlgorithm(), $path);

        return $hash . '.' . $extension;
    }

    public function __invoke(string|FileWrapper $file, array $actions, ?string $subprefix = null): ?string
    {
        if (empty($actions)) {
            return null;
        }

        $actionClassesString = $this->actionsToPath($actions);

        $newName = self::hashPath($file);

        $folder_1 = substr($newName, 0, $this->folder1Length);
        $folder_2 = substr($newName, $this->folder1Length, $this->folder2Length);

        $pathParts = collect([$this->prefix, $subprefix, $actionClassesString, $folder_1, $folder_2, $newName]);
        $path = $pathParts->filter()->implode(DIRECTORY_SEPARATOR);

        return $path;
    }

    public function actionsToPath(array $actions): string
    {
        if (empty($actions)) {
            throw new \InvalidArgumentException('Actions array must not be empty');
        }
        return implode(
            '_',
            array_map(
                fn(string $action) => Str::of($action)->classBasename()->swap($this->reducingClasses)->lower()->swap(['_' => '', '-' => ''])->toString(),
                $actions
            )
        );

        return $actionClassesString;
    }
}
