<?php

return [
    // 当无法从header通过时,从input数据中提取token的key
    'inputKey' => 'api_token',
    // 用户唯一关键词
    'keyName'  => 'id',
    // 签名算法
    'alg'      => 'HS512',
    // token有效时间(单位/分)
    'guards'   => [
        'api' => [
            'ttl'         => 1000,
            'refresh_ttl' => 2000,
            'key'         => env('JWT_SECRET'),
        ],
    ],
];
