<?php

return [
    'default'   => env('SMS_DRIVER', 'alidayu'),
    'expire_at' => env('SMS_EXPIRE_AT', 300),

    // 一信通
    'ums86'     => [
        'sp_code'   => env('UMS86_SP_CODE'),
        'app_key'   => env('UMS86_KEY'),
        'secret'    => env('UMS86_SECRET'),
        'expire_at' => env('SMS_EXPIRE_AT'),
    ],

    // 阿里大鱼
    'alidayu'   => [
        'app_key'   => env('ALIYUN_SMS_KEY'),
        'secret'    => env('ALIYUN_SMS_SECRET'),
        'sign_name' => env('ALIYUN_SMS_SIGN'),
        'product'   => env('ALIYUN_SMS_PRODUCT'),
        'region_id' => env('ALIYUN_SMS_REGION_ID'),
        'codes'     => [
            'register'     => env('ALIYUN_SMS_REGISTER_CODE'),
            'reset_pwd'    => env('ALIYUN_SMS_RESET_PWD_CODE'),
            'change_phone' => env('ALIYUN_SMS_CHANGE_PHONE_CODE'),
        ],
    ],
];
