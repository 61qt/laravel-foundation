<?php
namespace QT\Foundation\Providers;

use Overtrue\EasySms\EasySms;
use QT\Foundation\Sms\Ums86Gateway;
use Illuminate\Support\ServiceProvider;

// 注册短信服务
class SmsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/sms.php';

        $this->publishes([$configPath => config_path('sms.php')], 'sms');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [EasySms::class, 'sms', 'esay-sms'];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(EasySms::class, function ($app) {
            $manager = new EasySms($app->make('config')->get('sms', []));

            $this->extendEasySms($manager);

            return $manager;
        });

        $this->app->alias(EasySms::class, 'sms');

        $this->app->alias(EasySms::class, 'easy-sms');
    }

    /**
     * Extend easy sms.
     *
     * @return void
     */
    public function extendEasySms(EasySms $manager)
    {
        $manager->extend('ums86', function () {
            return new Ums86Gateway();
        });
    }
}
