<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Processors;

use Closure;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Vaskiq\LaravelFileLayer\Contracts\FileProcessorInterface;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

final class Thumbnail_300x300 implements FileProcessorInterface
{
    public const SIDE_SIZE = 300;

    protected readonly ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    public function handle(FileWrapper $file, Closure $next): FileWrapper
    {
        $image = $this->manager->read($file->fullPath());
        $image->cover(self::SIDE_SIZE, self::SIDE_SIZE);
        $image->save();

        return $next($file);
    }
}
