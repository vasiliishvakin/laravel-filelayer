<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\FileLayer\Traits;

use Vaskiq\LaravelFileLayer\Repositories\FileRepository;

trait WithFileRepository
{
    abstract private function fileRepository(): FileRepository;
}
