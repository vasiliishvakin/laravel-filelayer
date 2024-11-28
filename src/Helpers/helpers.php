<?php

use Symfony\Component\Filesystem\Path;

if (! function_exists('filelayer_log_context')) {
    function filelayer_log_context(array|string|Closure|null $context = null): array
    {
        $defaultContext = [
            'module' => 'FileLayer',
        ];

        $context = (function ($context) {
            if (is_null($context)) {
                return [];
            }

            if (is_string($context)) {
                if (class_exists($context)) {
                    $context = new $context;
                }
            }

            if (is_callable($context)) {
                return $context();
            }

            return is_array($context) ? $context : [$context];
        })($context);

        $context = array_merge($defaultContext, $context);

        return $context;
    }

    if (! function_exists('storage_path_normalize')) {
        function storage_path_normalize(string $path): string
        {
            return Path::canonicalize(ltrim($path, '/'));
        }
    }
}
