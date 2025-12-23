<?php
namespace Dharma\VideoUploader;

use Illuminate\Support\ServiceProvider;

class VideoUploaderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/video-uploader.php',
            'video-uploader'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/video-uploader.php' => config_path('video-uploader.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}