<?php 

namespace QT\Foundation\Contracts;

interface SmsClient
{
    /**
     * 发送验证码
     * 
     * @param $phone
     * @param $code
     * @param $template
     * @param $options
     */
    public function send($phone, $code, $template, array $options = []) : bool;
}