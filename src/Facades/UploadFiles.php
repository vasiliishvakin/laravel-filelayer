<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Facades;

use Illuminate\Support\Facades\Facade;
use Vaskiq\LaravelFileLayer\Services\UploadFilesService;

/**
 * @method static string joinPaths(string ...$segments) Join multiple path segments into a single path.
 * @method static string createFullPath(?string $newPath = null, ?string $fileName = null) Create a full path from optional directory and filename.
 * @method static FileWrapper uploadContent(string $path, string $content) Upload content to the specified path.
 * @method static FileWrapper uploadFileByPath(string $path, ?string $newPath = null, ?string $newFileName = null) Upload a file from a given local path.
 * @method static FileWrapper uploadLaravelUploaded(\Illuminate\Http\UploadedFile $file, ?string $newPath = null, ?string $newFileName = null) Upload a Laravel `UploadedFile` instance.
 * @method static FileWrapper|Collection uploadRequestFile(string $fieldName) Upload one or more files from an HTTP request.
 * @method static FileWrapper uploadBase64File(string $base64, ?string $newPath = null, ?string $newFileName = null) Upload a file from a Base64-encoded string.
 *
 * @see \Vaskiq\LaravelFileLayer\Services\UploadFilesService
 */
class UploadFiles extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UploadFilesService::class;
    }
}
