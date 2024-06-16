<?php

namespace EncoreDigitalGroup\MergeModels\Providers;

use EncoreDigitalGroup\MergeModels\ModelMerge;
use Illuminate\Support\ServiceProvider;

class MergeModelsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {

            // Publishing the configuration file.
            $this->publishes([
                __DIR__ . '/../../config/mergemodels.php' => config_path('mergemodels.php'),
            ], 'mergemodels.config');

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/mergemodels.php', 'mergemodels');

        // Register the service the package provides.
        $this->app->singleton('mergemodels', function ($app): \EncoreDigitalGroup\MergeModels\ModelMerge {
            return new ModelMerge();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['mergemodels'];
    }
}