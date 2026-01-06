<?php

namespace Aflorea4\LaravelSourceObfuscator;

use Illuminate\Support\ServiceProvider;
use Aflorea4\LaravelSourceObfuscator\Console\Commands\ObfuscateCommand;
use Aflorea4\LaravelSourceObfuscator\Console\Commands\ObfuscateCheckCommand;
use Aflorea4\LaravelSourceObfuscator\Console\Commands\ObfuscateClearCommand;
use Aflorea4\LaravelSourceObfuscator\Console\Commands\ObfuscateStatusCommand;
use Aflorea4\LaravelSourceObfuscator\Services\ObfuscationService;

class ObfuscatorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/obfuscator.php',
            'obfuscator'
        );

        $this->app->singleton(ObfuscationService::class, function ($app) {
            return new ObfuscationService(
                $app['config']->get('obfuscator'),
                $app->basePath()
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/obfuscator.php' => config_path('obfuscator.php'),
            ], 'config');

            // Register commands
            $this->commands([
                ObfuscateCommand::class,
                ObfuscateCheckCommand::class,
                ObfuscateClearCommand::class,
                ObfuscateStatusCommand::class,
            ]);
        }
    }
}

