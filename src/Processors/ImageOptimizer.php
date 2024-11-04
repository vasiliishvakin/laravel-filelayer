<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Processors;

use Closure;
use Spatie\ImageOptimizer\OptimizerChain;
use Vaskiq\LaravelFileLayer\Contracts\FileProcessorInterface;
use Vaskiq\LaravelFileLayer\Wrappers\BaseFileWrapper;
use Vaskiq\LaravelFileLayer\Wrappers\FileWrapper;

final class ImageOptimizer implements FileProcessorInterface
{
    public function __construct(private readonly OptimizerChain $optimizerChain) {}

    public function handle(BaseFileWrapper $file, Closure $next): FileWrapper
    {
        if (! $file->isLocal()) {
            return $next($file);
        }

        $this->optimizerChain->optimize($file->fullPath());

        return $next($file);
    }
}
