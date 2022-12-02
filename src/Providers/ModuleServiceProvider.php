<?php

namespace QT\Foundation\Providers;

use FilesystemIterator;
use Illuminate\Support\Collection;
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
        $repository = new ModuleRepository($this->app);

        $this->registerModuleService($repository);

        $repository->boot($moduleConfig);

        $this->app->instance('modules', $repository);

        $this->app->alias('modules', ModuleRepository::class);
    }

    /**
     * 注册模块服务
     * 
     * @param ModuleRepository $repository
     */
    protected function registerModuleService(ModuleRepository $repository)
    {
        // 注册命令
        $repository->registerLoader(function (Collection $config, string $path) {
            $path = "{$path}/{$config->get('command_path', 'Commands')}";

            if (!file_exists($path)) {
                return;
            }
    
            $commands = [];
            foreach (new FilesystemIterator($path) as $file) {
                if (!$file->isFile() || !$file->getExtension() === 'php') {
                    continue;
                }
    
                $commands[] = sprintf(
                    "%s\\Commands\\%s", 
                    $config['namespace'], 
                    substr($file->getFilename(), 0, -4)
                );
            }
    
            $this->commands($commands);
        });

        // 注册模块下资源
        $repository->registerLoader(function (Collection $config, string $path) {
            $path = "{$path}/{$config->get('resource_path', 'Resources')}";

            if (file_exists("{$path}/views")) {
                $this->loadViewsFrom("{$path}/views", $config['name']);
            }

            if (file_exists("{$path}/lang")) {
                $this->loadTranslationsFrom("{$path}/lang", $config['name']);
            }
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['modules', ModuleRepository::class];
    }
}
