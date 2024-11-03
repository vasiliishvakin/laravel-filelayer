<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Providers;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;
use Vaskiq\LaravelFileLayer\FileLayer;
use Vaskiq\LaravelFileLayer\Helpers\MimeHelper;
use Vaskiq\LaravelFileLayer\Repositories\FileRepository;
use Vaskiq\LaravelFileLayer\Services\ConfigPreprocessor;
use Vaskiq\LaravelFileLayer\Services\UploadFilesService;
use Vaskiq\LaravelFileLayer\Storage\StorageOperator;
use Vaskiq\LaravelFileLayer\TmpFileLayer;

class LaravelFileLayerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register any bindings, singletons, or other service configurations.
        $this->app->singleton(MimeHelper::class);

        $this->app->singleton(StorageOperator::class);
        $this->app->singleton(FileLayer::class);
        $this->app->singleton(FileRepository::class);

        $this->app->singleton(TmpFileLayer::class);

        $this->app->singleton(ConfigPreprocessor::class);

        $this->app->singleton(UploadFilesService::class);

        $this->app->singleton(ImageManager::class, function () {
            $config = config('filelayer.image_manager');

            return new ImageManager(
                driver: $config['driver'],
                options: $config['options'],
            );
        });
    }

    public function boot(): void
    {
        // Bootstrapping logic, such as publishing config files or migrations.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/filelayer.php' => config_path('filelayer.php'),
            'config',
        ]);

        ($this->app->make(ConfigPreprocessor::class))();
    }
}
