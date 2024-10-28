<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Spatie\LaravelData\Data;

class PathInfoData extends Data
{
    public readonly ?string $normalizedPath;

    public readonly ?string $directory;

    public readonly ?string $filename;

    public readonly ?string $extension;

    public function __construct(
        public readonly ?string $path = null,
        public readonly ?bool $isDirectory = null,
        string|false|null $directory = null,
        string|false|null $filename = null,
        string|false|null $extension = null,
    ) {
        $normalizedPath = Str::of($path)
            ->replace('\\', '/')
            ->replaceMatches('/\/+/', '/')
            ->ltrim('/');

        $pathInfo = $this->getPathInfo($normalizedPath, $isDirectory);

        $this->normalizedPath = (string) $normalizedPath;
        $this->directory = $directory ?? ($directory === false ? null : $pathInfo['directory']);
        $this->filename = $filename ?? ($filename === false ? null : $pathInfo['filename']);
        $this->extension = $extension ?? ($extension === false || $filename === false ? null : $pathInfo['extension']);
    }

    /**
     * Get detailed path information, determining if the path is a directory or a file.
     *
     * @param  Stringable  $path  The path to analyze, created using Str::of().
     * @param  bool|null  $isDirectory  Indicates if the path is a directory or a file:
     *                                  - true: Treat as a directory.
     *                                  - false: Treat as a file.
     *                                  - null: Determine based on the path (slash-ending or extension).
     * @return array{
     *     directory: string|null,  // Directory path or null if not applicable.
     *     filename: string|null,   // Filename without path, or null if directory.
     *     extension: string|null   // File extension or null if not a file.
     * }
     */
    private function getPathInfo(Stringable $path, ?bool $isDirectory = null): array
    {
        $trimmedPath = $path->trim('/');
        $fileName = $trimmedPath->basename();
        $extension = $this->getExtension($trimmedPath);
        $directory = (string) $trimmedPath === (string) $fileName ? null : $trimmedPath->dirname();

        if ($isDirectory === true) {
            return [
                'directory' => (string) $trimmedPath,
                'filename' => null,
                'extension' => null,
            ];
        }

        if ($isDirectory === false) {
            return [
                'directory' => (string) $directory,
                'filename' => (string) $fileName,
                'extension' => (string) $extension,
            ];
        }

        if ($trimmedPath->endsWith('/')) {
            return [
                'directory' => (string) $trimmedPath,
                'filename' => null,
                'extension' => null,
            ];
        }

        if (! empty($extension)) {
            return [
                'directory' => (string) $directory,
                'filename' => (string) $fileName,
                'extension' => (string) $extension,
            ];
        }

        return [
            'directory' => (string) $trimmedPath,
            'filename' => null,
            'extension' => null,
        ];
    }

    /**
     * Extract the file extension from a given path.
     *
     * @param  Stringable  $normalizedPath  The normalized path to extract the extension from.
     * @return string|null The normalized file extension or null if not present.
     */
    private function getExtension(Stringable $normalizedPath): ?string
    {
        $extension = $normalizedPath->afterLast('.')->lower();

        return $extension->isEmpty() ? null : $this->normalizedExtension((string) $extension);
    }

    /**
     * Normalize the file extension to a standard form (e.g., jpeg → jpg).
     *
     * @param  string|null  $extension  The original file extension.
     * @return string|null The normalized extension or null if not applicable.
     */
    private function normalizedExtension(?string $extension = null): ?string
    {
        return match ($extension) {
            'jpeg' => 'jpg',
            default => $extension,
        };
    }
}
