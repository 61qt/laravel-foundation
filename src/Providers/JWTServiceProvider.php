<?php 

namespace QT\Foundation\Providers;

use QT\Foundation\Auth\JWTGuard;
use QT\Foundation\Auth\JWTUserProvider;

class JWTServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/jwt.php';

        $this->mergeConfigFrom($configPath, 'jwt');
        $this->publishes([$configPath => config_path('jwt.php')], 'config');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app['auth']->provider('jwt-eloquent', function ($app, array $config) {
            return new JWTUserProvider($app['hash'], $config['model']);
        });

        $this->app['auth']->extend('jwt-auth', function ($app, $guard, array $config) {
            $options  = config('jwt');
            $provider = $app['auth']->createUserProvider($config['provider']);

            return new JWTGuard($app['request'], $provider, $guard, $options);
        });
    }
}