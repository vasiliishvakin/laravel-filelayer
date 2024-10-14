<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\StorageTools;

use Illuminate\Contracts\Container\Container;
use Monolog\Processor\ProcessorInterface;

class FileProcessOperator
{
    public function __construct(protected readonly Container $app) {}

    public function processorsAll(): array
    {
        return $this->app->tagged(ProcessorInterface::class);
    }

    public function processors(FileWrapper $file): array
    {
        // return array_filter($this->processorsAll(), fn(ProcessorInterface $processor) => $processor->isSupported($mime));
        return [];
    }
}
