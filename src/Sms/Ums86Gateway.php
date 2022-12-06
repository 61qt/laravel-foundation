<?php

namespace QT\Foundation\Sms;

use Throwable;
use GuzzleHttp\Client;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;

class Ums86Gateway implements GatewayInterface
{
    public const SMS_SERVER = 'http://sms.api.ums86.com:8899/sms/Api/Send.do';

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'usm86';
    }

    /**
     * {@inheritDoc}
     *
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface     $message
     * @param \Overtrue\EasySms\Support\Config                 $config
     *
     * @return array
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $content = $message->getContent($this);

        try {
            $response = (new Client())->post(self::SMS_SERVER, [
                'form_params' => [
                    'SpCode'         => $config['sp_code'],
                    'LoginName'      => $config['app_key'],
                    'Password'       => $config['secret'],
                    'expireAt'       => $config['expire_at'] ?? 300,
                    'UserNumber'     => $to->getNumber(),
                    'SerialNumber'   => time(),
                    'MessageContent' => iconv("UTF-8", "GB2312//IGNORE", $content),
                ],
            ]);
        } catch (Throwable $e) {
            throw new GatewayErrorException($e->getMessage(), 500);
        }

        $data = [];
        // 转换为UTF-8格式
        parse_str(iconv('GB2312', 'UTF-8//IGNORE', $response->getBody()->getContents()), $data);

        if ($data['result'] != '0') {
            throw new GatewayErrorException($data['description'], $data['result']);
        }

        return $data;
    }
}
