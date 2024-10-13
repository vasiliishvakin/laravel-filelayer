<?php

namespace Vaskiq\LaravelFileLayer\Providers;

use Illuminate\Support\ServiceProvider;
use Vaskiq\LaravelFileLayer\Repositories\FileRepository;
use Vaskiq\LaravelFileLayer\StorageManager;

class LaravelFileLayerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register any bindings, singletons, or other service configurations.
        $this->app->singleton(StorageManager::class);
        $this->app->singleton(FileRepository::class);
    }

    public function boot(): void
    {
        // Bootstrapping logic, such as publishing config files or migrations.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
