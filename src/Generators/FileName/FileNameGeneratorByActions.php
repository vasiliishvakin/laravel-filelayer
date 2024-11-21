<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Generators\FileName;

use Illuminate\Support\Str;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

class FileNameGeneratorByActions
{
    private readonly string $prefix;

    private readonly string $noActionsName;

    private readonly int $folder1Length;

    private readonly int $folder2Length;

    private readonly string $hashAlgorithm;

    public function __construct()
    {
        $this->prefix = config('filelayer.file_name_generator.prefix', 'processed');
        $this->noActionsName = config('filelayer.file_name_generator.no_actions_name', 'original');
        $this->folder1Length = config('filelayer.file_name_generator.folder_1_length', 1);
        $this->folder2Length = config('filelayer.file_name_generator.folder_2_length', 1);
        $this->hashAlgorithm = config('filelayer.file_name_generator.hash_algorithm', 'sha1');
    }

    public function __invoke(FileWrapper $file, array $actions): string
    {
        $actionClassesString = $this->actionsToPath($actions);

        $hashPath = Str::of($file->path())
            ->trim('/')
            ->lower()
            ->pipe(fn ($path) => Str::of(hash($this->hashAlgorithm, (string) $path)));

        $extension = $file->extension();
        $fileName = $hashPath->append('.')->append($extension);

        $folder_1 = $hashPath->substr(0, $this->folder1Length);
        $folder_2 = $hashPath->substr($this->folder1Length, $this->folder2Length);

        return implode(DIRECTORY_SEPARATOR, [$this->prefix(), $actionClassesString, $folder_1, $folder_2, $fileName]);
    }

    public function actionsToPath(array $actions): string
    {
        $actionClassesString = empty($actions)
            ? $this->noActionsName
            : implode(
                '_',
                array_map(
                    fn ($action) => Str::of($action)->classBasename()->lower()->replace(['-', '_'], ''),
                    $actions
                )
            );

        return $actionClassesString;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }
}
