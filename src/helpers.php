<?php

if (!function_exists('isDevelopEnv')) {
    function isDevelopEnv()
    {
        return config('app.env') === 'local';
    }
}

if (!function_exists('report_exception')) {
    function report_exception(Throwable $exception)
    {
        if (isDevelopEnv() || !app()->bound('sentry')) {
            return;
        }

        app('sentry')->tags_context(['HOSTNAME' => env('HOSTNAME')]);
        app('sentry')->captureException($exception);
    }
}

if (!function_exists('module_path')) {
    function module_path($path)
    {
        return base_path('modules' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('array_to_key')) {
    // 将数组内容进行hash之后组成key,避免如下问题
    // join('', ['aa', 'bb', 'cc']) === join('', ['aabbcc'])
    function array_to_key($array)
    {
        // 部分地方使用only函数获取的结构包含key
        // 所以去除key,用value组成新的数组
        // encode([1, 2, 3]) != encode(['a' => 1, 'b' => 2, 'c' => 3])
        return implode("\t", $array);
    }
}
