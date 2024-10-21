<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Generators\FileName;

use Illuminate\Support\Str;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

class FileNameGeneratorByActions
{
    const PREFIX = 'processed';

    public const NO_ACTIONS_NAME = 'original';

    public const FOLDER_1_LENGTH = 1;

    public const FOLDER_2_LENGTH = 1;

    public const HASH_ALGORITHM = 'sha256';

    public function __invoke(FileWrapper $file, array $actions): string
    {
        $actionClassesString = empty($actions)
            ? self::NO_ACTIONS_NAME
            : implode(
                '-',
                array_map(
                    fn ($action) => Str::of($action)->classBasename()->snake('-')->lower(),
                    $actions
                )
            );

        $hashPath = Str::of($file->path())
            ->lower()
            ->pipe(fn ($path) => Str::of(hash(self::HASH_ALGORITHM, (string) $path))->lower());

        $extension = $file->extension();
        $fileName = $hashPath->append('.')->append($extension);

        $folder_1 = $hashPath->substr(0, self::FOLDER_1_LENGTH);
        $folder_2 = $hashPath->substr(self::FOLDER_1_LENGTH, self::FOLDER_2_LENGTH);

        return implode(DIRECTORY_SEPARATOR, [static::PREFIX, $actionClassesString, $folder_1, $folder_2, $fileName]);
    }
}
