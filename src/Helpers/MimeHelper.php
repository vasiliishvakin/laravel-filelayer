<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Helpers;

use Symfony\Component\Mime\MimeTypes;

class MimeHelper
{
    public function __construct(protected readonly MimeTypes $mimeTypes) {}

    public function getExtensions(string $mimeType): array
    {
        return $this->mimeTypes->getExtensions($mimeType);
    }

    public function getMimeTypes(string $ext): array
    {
        return $this->mimeTypes->getMimeTypes($ext);
    }

    public function extension(string $mimeType): string
    {
        return $this->mimeTypes->getExtensions($mimeType)[0] ?? '';
    }
}
