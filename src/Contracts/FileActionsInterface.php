<?php

declare(strict_types=1);

use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

interface FileActionsInterface
{
    public function get(FileWrapper $file): string;

    public function exists(FileWrapper $file): bool;

    public function delete(FileWrapper $file): bool;

    // public function copy(string $from, string $to): bool;

    // public function move(string $from, string $to): bool;

}
