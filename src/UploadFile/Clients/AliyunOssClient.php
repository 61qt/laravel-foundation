<?php

namespace QT\Foundation\UploadFile\Clients;

use Exception;
use SplFileInfo;
use OSS\OssClient;
use QT\Foundation\Contracts\UploadFileClient;

class AliyunOssClient implements UploadFileClient
{
    protected $ossClient;

    public function __construct(array $config)
    {
        if (!class_exists(OssClient::class)) {
            throw new Exception("依赖\"aliyuncs/oss-sdk-php\",请引入后再试");
        }

        if (empty($config['access_key_id'])) {
            throw new Exception('阿里云oss app_id 不能为空');
        }

        if (empty($config['access_key_secret'])) {
            throw new Exception('阿里云oss app_secret 不能为空');
        }

        if (empty($config['ram_end_point'])) {
            // 默认使用深圳节点
            $config['ram_end_point'] = 'oss-cn-shenzhen.aliyuncs.com';
        }

        $this->ossClient = new OssClient(
            $config['access_key_id'],
            $config['access_key_secret'],
            $config['ram_end_point']
        );
    }

    public function upload($filename, $bucket, array $options = []): string
    {
        $file = new SplFileInfo($filename);

        if (isset($options['timeout']) && $options['timeout'] > 0) {
            $this->ossClient->setTimeout($options['timeout']);
        }
        if (isset($options['connect_timeout']) && $options['connect_timeout'] > 0) {
            $this->ossClient->setTimeout($options['connect_timeout']);
        }
        if (isset($options['use_ssl'])) {
            $this->ossClient->setUseSSL($options['use_ssl']);
        }
        if (isset($options['max_retry'])) {
            $this->ossClient->setTimeout($options['max_retry']);
        }

        $name = !empty($options['object'])
            ? $options['object']
            : $file->getFilename();

        $result = $this->ossClient->uploadFile($bucket, $name, $filename);

        if (empty($result['info']['url'])) {
            throw new Exception('Upload error');
        }

        // 返回图片上传后可访问的地址
        return $result['info']['url'];
    }

    public function delete($bucket, $name): bool
    {
        $this->ossClient->deleteObject($bucket, $name);
        // 如果删除错误会抛出异常，只要执行到此处就认为删除成功
        return true;
    }

    public function __call($method, $params)
    {
        return $this->ossClient->{$method}(...$params);
    }
}
