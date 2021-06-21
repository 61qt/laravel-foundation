<?php

namespace QT\Foundation\Exceptions;

use RuntimeException;

class Error extends RuntimeException
{
    const DEFAULT_ERR_CODE = 1000;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var bool
     */
    protected $reportable = false;

    /**
     * @param string $errCode
     * @param string $message
     * @param array $data
     */
    public function __construct($errCode = 'SYSTEM_FAILED', $message = '', $data = [])
    {
        $config        = config("errors.{$errCode}");
        $this->code    = isset($config['code']) ? $config['code'] : self::DEFAULT_ERR_CODE;
        $this->message = empty($message) && isset($config['msg']) ? $config['msg'] : $message;
        $this->data    = $data;
    }

    /**
     * @param array $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 变为可上报错误
     * 
     * @param bool $reportable
     */
    public function reportable(bool $reportable = true)
    {
        $this->reportable = $reportable;

        return $this;
    }

    /**
     * 是否要上报错误信息
     * 
     * @return bool
     */
    public function shouldReport(): bool
    {
        return $this->reportable;
    }
}
