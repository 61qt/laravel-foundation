<?php

namespace QT\Foundation\Exceptions;

use RuntimeException;

class Error extends RuntimeException
{
    const DEFAULT_ERR_CODE = 1000;

    protected $data = [];

    protected $shouldReport = false;

    public function __construct($errCode = 'SYSTEM_FAILED', $message = '', $data = [])
    {
        $config        = config("errors.{$errCode}");
        $this->code    = isset($config['code']) ? $config['code'] : self::DEFAULT_ERR_CODE;
        $this->message = empty($message) && isset($config['msg']) ? $config['msg'] : $message;
        $this->data    = $data;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setShouldReport($shouldReport = true)
    {
        $this->shouldReport = $shouldReport;

        return $this;
    }

    public function shouldReport()
    {
        return $this->reportable;
    }
}