<?php
namespace QT\Foundation\Providers;

use QT\Foundation\Sms\SmsManager;
use QT\Foundation\Contracts\SmsClient;
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
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sms', function ($app) {
            $config = $app->make('config')->get('sms', []);

            return new SmsManager($config);
        });

        $this->app->singleton(SmsClient::class, function ($app) {
            return $app->make('sms')->getClient();
        });
    }
}
