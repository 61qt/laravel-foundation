<?php

namespace QT\Foundation\UploadFile;

use InvalidArgumentException;
use QT\Foundation\Contracts\UploadFileClient;

class UploadManager
{
    protected $config;

    protected $clients;

    /**
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get upload service client.
     *
     * @param  string|null  $name
     * @return UploadFileClient
     */
    public function getClient($name = null)
    {
        $name = $name ?: $this->config['default'];

        if (isset($this->clients[$name])) {
            return $this->clients[$name];
        }

        return $this->clients[$name] = $this->resolve($name);
    }

    /**
     * Resolve upload service.
     *
     * @param  string|null  $name
     * @return UploadFileClient
     */
    protected function resolve($name)
    {
        $config = $this->config[$name] ?? [];

        switch ($name) {
            case 'fastDFS':
                return new Clients\FastDFSClient($config);
            case 'aliyunOss':
                return new Clients\AliyunOssClient($config);
            // TODO 接入七牛上传文件
            case 'qiniu':
            default:
                throw new InvalidArgumentException("Upload service [{$name}] not configured.");
        }
    }

    /**
     * @param  string  $method
     * @param  array  $parameters
     * @return UploadFileClient
     */
    public function __call($method, $parameters)
    {
        return $this->getClient()->{$method}(...$parameters);
    }
}
