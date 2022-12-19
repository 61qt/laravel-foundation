<?php

if (!function_exists('isDevelopEnv')) {
    /**
     * 判断是否开发环境
     *
     * @return boolean
     */
    function isDevelopEnv(): bool
    {
        return config('app.env') === 'local';
    }
}

if (!function_exists('module_path')) {
    /**
     * 获取模块的路径
     *
     * @param string $path
     * @return string
     */
    function module_path(string $path): string
    {
        return base_path('modules' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('array_to_key')) {
    /**
     * 将数组加上\t生成key
     * 避免join('', ['aa', 'bb', 'cc']) === join('', ['aabbcc'])
     * @param array $array
     * @return string
     */
    function array_to_key(array $array): string
    {
        return implode("\t", $array);
    }
}
