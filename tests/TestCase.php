<?php

namespace Dharma\VideoUploader\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Dharma\VideoUploader\VideoUploaderServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            VideoUploaderServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Mock FFmpeg here if needed
        $this->app->bind('laravel-ffmpeg', function () {
            return new class {
                public function fromDisk($disk) { return $this; }
                public function open($path) { return $this; }
                public function exportForHLS() { return $this; }
                public function toDisk($disk) { return $this; }
                public function addFormat($format, $callback = null) { if (is_callable($callback)) $callback($this); return $this; }
                public function scale($w, $h = null) { return $this; }
                public function export() { return $this; }
                public function inFormat($format) { return $this; }
                public function save($path) { return true; }
            };
        });
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}