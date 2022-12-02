<?php

namespace QT\Foundation;

use FilesystemIterator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use QT\Foundation\Exceptions\Error;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Foundation\Application;

/**
 * 模块配置信息
 * 
 * @package QT\Foundation
 */
class ModuleRepository
{
    /**
     * 模块配置
     * 
     * @var array
     */
    protected $modules = [];

    /**
     * 模块加载器
     * 
     * @var array<callable>
     */
    protected $loaders = [];

    /**
     * @param Application $app
     */
    public function __construct(protected Application $app)
    {
        $this->registerLoader([$this, 'loadRoute']);
        $this->registerLoader([$this, 'loadGraphqlSchema']);
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
     * 注册模块服务
     * 
     * @param callable $loader
     * @return self
     */
    public function registerLoader(callable $loader)
    {
        $this->loaders[] = $loader;

        return $this;
    }

    /**
     * @param array $moduleConfig
     */
    public function boot(array $moduleConfig)
    {
        $namespace  = Arr::get($moduleConfig, 'namespace', 'Modules\\');
        $configFile = Arr::get($moduleConfig, 'config_file', 'config.php');
        $middleware = Arr::get($moduleConfig, 'http.middleware', []);

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

            // 初始化模块配置
            foreach ($this->loaders as $loader) {
                call_user_func($loader, $config, $path);
            }

            $this->modules[$config['name']] = $config;
        }
    }

    /**
     * 加载路由
     * 
     * @param Collection<string, mixed> $config
     * @param string $path
     */
    protected function loadRoute(Collection $config, string $path)
    {
        $path = "{$path}/{$config->get('route_file', 'route.php')}";

        if (!file_exists($path)) {
            return;
        }

        $name = $config['name'];
        $func = fn ($middleware) => str_replace('{module}', $name, $middleware);

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

        $register->as("{$name}.")
            ->prefix($name)
            ->namespace($namespace)
            ->group($path);
    }

    /**
     * 加载graphql语法信息
     * 
     * @param Collection $config
     * @param string $path
     */
    protected function loadGraphqlSchema(Collection $config, string $path)
    {
        $path = "{$path}/{$config->get('graphql_file', 'graphql.php')}";

        // 获取graphql语法
        if (file_exists($path)) {
            $config->offsetSet('graphql', ['schema' => require $path]);
        }
    }
}
