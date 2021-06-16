<?php

return [
    // 文件夹目录名称,子模块存放的目录
    'path'          => base_path('modules'),
    // 命名空间
    'namespace'     => 'Modules\\',
    // 配置文件名称
    'config_file'   => 'config.php',
    // 路由设置
    'route_file'    => 'route.php',
    // 启用graphql名称
    'graphql_file'  => 'graphql.php',
    // resources 目录(views, lang)
    'resource_path' => 'Resources',
    // 中间件
    'http'          => [
        // 可用模板变量
        // module => 模块名称
        'middleware' => [
            "load_module:{module}",
        ],
    ],
];
