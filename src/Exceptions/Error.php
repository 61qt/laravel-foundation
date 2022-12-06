<?php

namespace QT\Foundation\Exceptions;

use RuntimeException;

/**
 * 业务错误
 *
 * @package QT\Foundation\Exceptions
 */
class Error extends RuntimeException
{
    public const DEFAULT_ERR_CODE = 1000;

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
    public function __construct(string $errCode = 'SYSTEM_FAILED', string $message = '', array $data = [])
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
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 变为可上报错误
     *
     * @param boolean $reportable
     * @return self
     */
    public function reportable(bool $reportable = true): self
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
