<?php

namespace Huztw\Seeder;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;

class SeederServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        AboutCommand::add('Seeder', 'Version', '1.0.0');

        $this->mergeConfigFrom(__DIR__.'/../config/seeder.php', 'seeder');

        // Register the service the package provides.
        $this->app->singleton('seeder', function ($app) {
            return new Seeder;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['seeder'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/seeder.php' => config_path('seeder.php'),
        ], 'seeder.config');

        // Registering package commands.
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Huztw\Seeder\Console\SeederCommand::class,
            ]);
        }
    }
}
