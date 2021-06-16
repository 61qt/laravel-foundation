<?php

namespace QT\Foundation\Providers;

use QT\Foundation\ModuleRepository;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/config.php';

        $this->mergeConfigFrom($configPath, 'modules');
        $this->publishes([$configPath => config_path('modules.php')], 'config');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $moduleConfig = $this->app['config']->get('modules');

        if (empty($moduleConfig) || empty($moduleConfig['path'])) {
            return;
        }
        // 因为路由需要提前加载,所以不绑定回调而是绑定已有的实例
        $repository = new ModuleRepository($this->app, $moduleConfig);

        $this->app->instance('modules', $repository);

        $this->app->alias('modules', ModuleRepository::class);
    }
}
