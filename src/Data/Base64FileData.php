<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Data;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Vaskiq\LaravelFileLayer\Facades\Mime;

class Base64FileData extends Data
{
    #[Computed]
    public readonly string $extension;

    public function __construct(
        public readonly string $base64,
        public readonly string $type,

    ) {
        $this->extension = Mime::extension($this->type);
    }
}
