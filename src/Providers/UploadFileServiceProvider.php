<?php
namespace QT\Foundation\Providers;

use App\Utils\UploadFile\UploadManager;
use Illuminate\Support\ServiceProvider;
use QT\Foundation\Contracts\UploadFileClient;

// 注册文件上传服务
class UploadFileServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/upload.php';

        $this->publishes([$configPath => config_path('upload.php')], 'upload');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('uploadFile', function ($app) {
            $config = $app->make('config')->get('upload', []);

            return new UploadManager($config);
        });

        $this->app->singleton(UploadFileClient::class, function ($app) {
            return $app->make('uploadFile')->getClient();
        });
    }
}
