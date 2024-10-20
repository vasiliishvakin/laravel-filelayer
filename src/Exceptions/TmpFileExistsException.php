<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Exceptions;

class TmpFileExistsException extends \Exception
{
    public static function fromPath(string $path, ?string $storage = 'storage'): self
    {
        return new self(sprintf('Tmp file already exists in %s: %s', $storage, $path));
    }
}
