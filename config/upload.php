<?php

return [
    'default'   => env('UPLOAD_DRIVER', 'aliyunOss'),
    'bucket'    => env('UPLOAD_BUCKET', 'jiamei-uploads'),
    'fastDFS'   => [
        'host' => env('FILE_URL'),
        // FastDFS 配置在 FastDFS 扩展内配置
    ],

    'aliyunOss' => [
        'access_key_id'     => env('ALIYUN_OSS_ACCESS_KEY_ID'),
        'access_key_secret' => env('ALIYUN_OSS_ACCESS_KEY_SECRET'),
        'ram_region_id'     => env('ALIYUN_OSS_RAM_REGION_ID'),
        'ram_end_point'     => env('ALIYUN_OSS_RAM_END_POINT'),
        'ram_role_arn'      => env('ALIYUN_OSS_RAM_ROLE_ARN'),
        'role_session_name' => env('ALIYUN_OSS_ROLE_SESSION_NAME')
    ],

    'qiniu'     => [
        'qiniu_ak'              => env('QINIU_AK'),
        'qiniu_sk'              => env('QINIU_SK'),
        'qiniu_bucket'          => env('QINIU_BUCKET'),
        'qiniu_private_bucket'  => env('QINIU_PRIVATE_BUCKET'),
        'qiniu_callback_url'    => env('QINIU_CALLBACK_URL'),
        'logo_center_watermark' => env('QINIU_WATERMARK'),
    ]
];