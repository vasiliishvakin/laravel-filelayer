<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Processors;

use Closure;
use Intervention\Image\ImageManager;
use Vaskiq\LaravelFileLayer\Contracts\FileProcessorInterface;
use Vaskiq\LaravelFileLayer\Wrappers\BaseFileWrapper;

final class Thumbnail_300x300 implements FileProcessorInterface
{
    public const SIDE_SIZE = 300;

    public function __construct(private readonly ImageManager $manager) {}

    public function handle(BaseFileWrapper $file, Closure $next): BaseFileWrapper
    {
        $image = $this->manager->read($file->fullPath());
        $image->cover(self::SIDE_SIZE, self::SIDE_SIZE);
        $image->save();

        return $next($file);
    }
}
