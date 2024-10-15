<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Providers;

use Illuminate\Support\ServiceProvider;
use Vaskiq\LaravelFileLayer\Facades\Mime;
use Vaskiq\LaravelFileLayer\Helpers\MimeHelper;
use Vaskiq\LaravelFileLayer\Repositories\FileRepository;
use Vaskiq\LaravelFileLayer\StorageManager;
use Vaskiq\LaravelFileLayer\StorageTools\StorageOperator;

class LaravelFileLayerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register any bindings, singletons, or other service configurations.
        $this->app->singleton(MimeHelper::class);

        $this->app->singleton(StorageOperator::class);
        $this->app->singleton(StorageManager::class);
        $this->app->singleton(FileRepository::class);
    }

    public function boot(): void
    {
        // Bootstrapping logic, such as publishing config files or migrations.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Mime', Mime::class);
        });
    }
}
