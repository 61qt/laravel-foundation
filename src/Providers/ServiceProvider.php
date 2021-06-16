<?php

namespace QT\Foundation\Providers;

use QT\Foundation\Console\ModelMakeCommand;
use QT\Foundation\Console\ResolverMakeCommand;
use QT\Foundation\Console\GraphQLTypeMakeCommand;
use QT\Foundation\Console\GraphQlMutationMakeCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/errors.php';

        $this->publishes([$configPath => config_path('errors.php')], 'errors');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register commands
        $this->commands([
            ModelMakeCommand::class,
            ResolverMakeCommand::class,
            GraphQLTypeMakeCommand::class,
            GraphQlMutationMakeCommand::class,
        ]);
    }
}