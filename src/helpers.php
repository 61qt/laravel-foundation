<?php

if (!function_exists('isDevelopEnv')) {
    /**
     * 判断是否开发环境
     *
     * @return bool
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
     *
     * @param array $array
     * @return string
     */
    function array_to_key(array $array): string
    {
        return implode("\t", $array);
    }
}

if (!function_exists('isDebug')) {
    /**
     * 判断是否debug模式
     *
     * @return bool
     */
    function isDebug(): bool
    {
        return config('app.debug');
    }
}

if (!function_exists('array_insert')) {
    /**
     * 在指定位置插入数组
     *
     * @param array $array
     * @param string|int $key
     * @param array $replacement
     * @return array
     */
    function array_insert(array $array, string|int $key, array $replacement): array
    {
        if (empty($replacement)) {
            return $array;
        }

        if (array_is_list($array)) {
            array_splice($array, $key, 1, $replacement);

            return $array;
        }

        $result = [];
        foreach ($array as $index => $value) {
            if ($index === $key) {
                $result = array_merge($result, $replacement);
                continue;
            }
            $result[$index] = $value;
        }

        return $result;
    }
}
