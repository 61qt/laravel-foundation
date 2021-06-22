<?php

namespace QT\Foundation\Sms\Clients;

use Exception;
use AlibabaCloud\Client\AlibabaCloud;
use QT\Foundation\Contracts\SmsClient;

class AlidayuSms implements SmsClient
{
    protected $config = [];

    public function __construct(array $config)
    {
        if (!class_exists(AlibabaCloud::class)) {
            throw new Exception("依赖\"alibabacloud/client\",请引入后再用");
        }

        foreach (['app_key', 'secret', 'sign_name'] as $key) {
            if (empty($config[$key])) {
                throw new Exception("{$key} 不能为空");
            }
        }

        $this->config = $config;
    }

    public function send($phone, $code, $template, array $options = []): bool
    {
        // 设置全局Client
        AlibabaCloud::accessKeyClient($this->config['app_key'], $this->config['secret'])
            ->regionId($this->config['region_id'] ?? 'cn-hangzhou')
            ->asDefaultClient();

        if (empty($this->config['codes'][$template])) {
            throw new Exception("无效的短信模板 {$template}");
        }

        $queryParams = [
            'PhoneNumbers'  => $phone,
            'SignName'      => $this->config['sign_name'],
            'TemplateCode'  => $this->config['codes'][$template],
            'TemplateParam' => json_encode(['code' => $code]),
        ];

        $options = array_merge($options, ['query' => $queryParams]);

        AlibabaCloud::rpc()
            ->product('Dysmsapi')
            ->scheme('http')
            ->version('2017-05-25')
            ->action('SendSms')
            ->method('POST')
            ->options($options)
            ->request();

        return true;
    }
}
