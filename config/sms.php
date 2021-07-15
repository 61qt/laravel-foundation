<?php

return [
    // HTTP 请求的超时时间（秒）
    'timeout'  => 5.0,

    // 默认发送配置
    'default'  => [
        // 网关调用策略，默认：顺序调用
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => [
            'aliyun',
        ],
    ],

    // 可用的网关配置
    'gateways' => [
        'errorlog' => [
            'file' => storage_path('logs/easy-sms.log'),
        ],
        // 一信通
        'ums86'    => [
            'sp_code'   => env('UMS86_SP_CODE'),
            'app_key'   => env('UMS86_KEY'),
            'secret'    => env('UMS86_SECRET'),
            'expire_at' => env('SMS_EXPIRE_AT'),
        ],
        // 阿里大鱼
        'aliyun'   => [
            'app_key'   => env('ALIYUN_SMS_KEY'),
            'secret'    => env('ALIYUN_SMS_SECRET'),
            'sign_name' => env('ALIYUN_SMS_SIGN'),
        ],
    ],
];
