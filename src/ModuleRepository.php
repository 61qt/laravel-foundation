<?php

namespace QT\Foundation;

use FilesystemIterator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use QT\Foundation\Exceptions\Error;
use Illuminate\Support\Facades\Route;
use Illuminate\Console\Application as Console;
use Illuminate\Contracts\Foundation\Application;

/**
 * 模块配置信息
 * 
 * @package QT\Foundation
 */
class ModuleRepository
{
    /**
     * @var array
     */
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
    public function get(string $module, $default = null): Collection
    {
        return Arr::get($this->modules, $module, $default);
    }

    /**
     * @param string $key
     * @return Collection
     */
    public function has(string $module)
    {
        return isset($this->modules[$module]);
    }

    /**
     * @param string $key
     * @return Collection
     */
    public function config(string $module): Collection
    {
        return $this->modules[$module];
    }

    /**
     * @return array
     */
    public function modules(): array
    {
        return $this->modules;
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
        $commandPath  = Arr::get($moduleConfig, 'command_path', 'Commands');
        $middleware   = Arr::get($moduleConfig, 'http.middleware', []);

        foreach (new FilesystemIterator($moduleConfig['path']) as $file) {
            if (!$file->isDir()) {
                continue;
            }

            $path = $file->getPathname();
            $name = mb_strtolower($file->getFilename());

            $configPath = "{$path}/{$configFile}";
            if (file_exists($configPath)) {
                $config = require $configPath;
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

            // TODO 初始化逻辑支持自定义扩展 
            // register('config_key', resolver)
            $this->registerRoute($config, "{$path}/{$routeFile}");
            $this->registerCommands($config, "{$path}/{$commandPath}");
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
    protected function registerCommands(Collection $config, string $path)
    {
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

        Console::starting(function ($artisan) use ($commands) {
            $artisan->resolveCommands($commands);
        });
    }

    /**
     * @param Collection $config
     * @param string $path
     */
    protected function registerResources(Collection $config, string $path)
    {
        if (file_exists("{$path}/views")) {
            $this->app->get('view')->addNamespace($config['name'], "{$path}/views");
        }

        if (file_exists("{$path}/lang")) {
            $this->app->get('translator')->addNamespace($config['name'], "{$path}/lang");
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
