<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Contracts;

use Stringable;

interface FsInfoInterface extends Stringable
{
    public function fullPath(): string;

    public function name(): string;

    public function extension(): ?string;

    public function size(): ?int;

    public function mime(): ?string;
}
