<?php

namespace QT\Foundation\Sms\Clients;

use Exception;
use GuzzleHttp\Client;
use QT\Foundation\Exceptions\Error;
use QT\Foundation\Contracts\SmsClient;

class Ums86Sms implements SmsClient
{
    const SMS_SERVER = 'http://sms.api.ums86.com:8899/sms/Api/Send.do';

    const VERIFY_CODE_TEMPLATE = "您本次验证码为%s";

    const TEMPLATES = [
        'register'     => '您本次验证码为%s',
        'reset_pwd'    => '您本次验证码为%s',
        'change_phone' => '验证码%s您正在重新绑定手机号,3分钟内有效,请不要泄露验证码给他人。',
    ];

    public function __construct(array $config)
    {
        foreach (['sp_code', 'app_key', 'secret'] as $key) {
            if (empty($config[$key])) {
                throw new Exception("{$key} 不能为空");
            }
        }

        $this->config = $config;
    }

    public function send($phone, $code, $template, array $options = []): bool
    {
        $template = self::TEMPLATES[$template];
        $content  = sprintf($template, $code);
        $params   = [
            'SpCode'         => $this->config['sp_code'],
            'LoginName'      => $this->config['app_key'],
            'Password'       => $this->config['secret'],
            'expireAt'       => $this->config['expire_at'] ?? 300,
            'UserNumber'     => $phone,
            'SerialNumber'   => time(),
            'MessageContent' => iconv("UTF-8", "GB2312//IGNORE", $content),
        ];

        try {
            $response = (new Client())->post(self::SMS_SERVER, ['form_params' => $params]);
        } catch (\Exception$e) {
            throw new Error('SYSTEM_FAILED', $e->getMessage());
        }

        // 转换为UTF-8格式
        parse_str(iconv('GB2312', 'UTF-8//IGNORE', $response->getBody()->getContents()), $data);

        if ($data['result'] != '0') {
            throw new Error('SYSTEM_FAILED', "短信发送失败: {$data['description']}");
        }

        return true;
    }
}
