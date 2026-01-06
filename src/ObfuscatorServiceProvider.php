<?php

namespace AlexandruFlorea\LaravelSourceObfuscator;

use Illuminate\Support\ServiceProvider;
use AlexandruFlorea\LaravelSourceObfuscator\Console\Commands\ObfuscateCommand;
use AlexandruFlorea\LaravelSourceObfuscator\Console\Commands\ObfuscateCheckCommand;
use AlexandruFlorea\LaravelSourceObfuscator\Console\Commands\ObfuscateClearCommand;
use AlexandruFlorea\LaravelSourceObfuscator\Console\Commands\ObfuscateStatusCommand;
use AlexandruFlorea\LaravelSourceObfuscator\Services\ObfuscationService;

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

