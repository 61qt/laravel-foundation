<?php

$errorCode = 1000;

/**
 * 自定义错误配置
 * 
 * statusCode 只在restful请求出错时生效
 */
return [
    'UNAUTH'            => [
        'code'       => 401,
        'statusCode' => 401,
        'msg'        => '用户未登陆或已过期',
    ],
    'FORBIDDEN'         => [
        'code'       => 403,
        'statusCode' => 403,
        'msg'        => '禁止访问',
    ],
    'NOT_FOUND'         => [
        'code'       => 404,
        'statusCode' => 404,
        'msg'        => 'NOT FOUND',
    ],
    'CONFLICT'          => [
        'code'       => 409,
        'statusCode' => 409,
        'msg'        => '数据已存在',
    ],
    'INVALID_PARAM'     => [
        'code'       => 422,
        'statusCode' => 422,
        'msg'        => '无效参数',
    ],
    'TOO_MANY_REQUESTS' => [
        'code'       => 429,
        'statusCode' => 429,
        'msg'        => '访问过于频繁,请稍后再试',
    ],
    'SYSTEM_FAILED'     => [
        'code'       => 500,
        'statusCode' => 500,
        'msg'        => '系统内部错误,请联系网站管理员',
    ],

    // Custom error
    // 'FOOBAR' => [
    //      'code' => $errorCode++,
    //      'msg'  => 'foobar',
    // ]
];
