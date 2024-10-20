<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Services;

use Illuminate\Support\Facades\Cache;
use Vaskiq\LaravelFileLayer\StorageTools\StorageOperator;

class ConfigPreprocessor
{
    public const CACHE_KEY = 'filtered_disks_config';

    private const CACHE_TTL = 0;

    public function __invoke(): void
    {
        $this->apply();
    }

    public function apply(): void
    {

        $filteredConfig = self::CACHE_TTL > 0
            ? Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                return $this->filterConfig();
            })
            : $this->filterConfig();

        config([StorageOperator::CONFIG_DISKS_KEY => $filteredConfig]);
    }

    private function filterConfig(): array
    {
        $config = config(StorageOperator::CONFIG_DISKS_KEY, []);

        return array_filter($config, function ($diskConfig) {
            if (isset($diskConfig['disabled']) && $diskConfig['disabled'] === true) {
                return false;
            } elseif ($diskConfig['driver'] === 's3' && (! isset($diskConfig['key']) || ! isset($diskConfig['secret']))) {
                return false;
            }

            return true;
        });
    }
}
