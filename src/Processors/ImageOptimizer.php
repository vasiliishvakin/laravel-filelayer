<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Processors;

use Closure;
use Spatie\ImageOptimizer\OptimizerChain;
use Vaskiq\LaravelFileLayer\Contracts\FileProcessorInterface;
use Vaskiq\LaravelFileLayer\Contracts\FsInfoInterface;

final class ImageOptimizer implements FileProcessorInterface
{
    public function __construct(private readonly OptimizerChain $optimizerChain) {}

    public function handle(FsInfoInterface $file, Closure $next): FsInfoInterface
    {
        $this->optimizerChain->optimize($file->fullPath());

        return $next($file);
    }
}
