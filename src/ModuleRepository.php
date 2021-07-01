<?php

namespace QT\Foundation;

use FilesystemIterator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use QT\Foundation\Exceptions\Error;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Foundation\Application;

class ModuleRepository
{
    protected $modules = [];

    /**
     * @param Application $app
     * @param array $moduleConfig
     */
    public function __construct(protected Application $app, array $moduleConfig)
    {
        $this->boot($moduleConfig);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return Collection
     */
    public function config(string $module): Collection
    {
        return $this->modules[$module];
    }

    /**
     * @param array $moduleConfig
     */
    public function boot(array $moduleConfig)
    {
        $namespace    = Arr::get($moduleConfig, 'namespace', 'Modules\\');
        $configFile   = Arr::get($moduleConfig, 'config_file', 'config.php');
        $routeFile    = Arr::get($moduleConfig, 'route_file', 'route.php');
        $graphqlFile  = Arr::get($moduleConfig, 'graphql_file', 'graphql.php');
        $resourcePath = Arr::get($moduleConfig, 'resource_path', 'Resources');
        $middleware   = Arr::get($moduleConfig, 'http.middleware', []);

        foreach (new FilesystemIterator($moduleConfig['path']) as $file) {
            if (!$file->isDir()) {
                continue;
            }

            $path = $file->getPathname();
            $name = mb_strtolower($file->getFilename());

            $configFile = "{$path}/{$configFile}";
            if (file_exists($configFile)) {
                $config = require $configFile;
            } else {
                $config = ['name' => $name];
            }

            if (!is_array($config)) {
                throw new Error('SYSTEM_FAILED', "{$name}模块配置错误,加载失败");
            }

            $config = collect(['middleware' => $middleware])->merge($config);

            if (empty($config['namespace'])) {
                // 没设置的命名空间,使用默认生成的
                $config['namespace'] = $namespace . Str::ucfirst(Str::camel($name));
            }

            $this->registerRoute($config, "{$path}/{$routeFile}");
            $this->registerResources($config, "{$path}/{$resourcePath}");
            $this->registerGraphqlSchema($config, "{$path}/{$graphqlFile}");

            $this->modules[$config['name']] = $config;
        }
    }

    /**
     * @param Collection $config
     * @param string $path
     */
    protected function registerRoute(Collection $config, string $path)
    {
        if (!file_exists($path)) {
            return;
        }

        $name = $config['name'];
        $func = function ($middleware) use ($name) {
            return str_replace('{module}', $name, $middleware);
        };

        // 初始化中间件
        $register = Route::middleware(array_map($func, $config['middleware']));

        // 检查是否要自定义路由
        if (Arr::get($config, 'route.customize', false)) {
            return $register->group($path);
        }

        $namespace = Arr::get(
            $config,
            'route.namespace',
            "{$config['namespace']}\\Http\\Controllers"
        );

        $register->prefix($name)->namespace($namespace)->group($path);
    }

    /**
     * @param Collection $config
     * @param string $path
     */
    protected function registerResources(Collection $config, string $path)
    {
        if (file_exists("{$path}/views")) {
            $this->app->get('view')->addNamespace("{$path}/views", $config['name']);
        }

        if (file_exists("{$path}/lang")) {
            $this->app->get('translator')->addNamespace("{$path}/lang", $config['name']);
        }
    }

    /**
     * @param Collection $config
     * @param string $path
     */
    protected function registerGraphqlSchema(Collection $config, string $path)
    {
        // 获取graphql语法
        if (file_exists($path)) {
            $config->offsetSet('graphql', ['schema' => require $path]);
        }
    }
}
