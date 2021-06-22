<?php

namespace QT\Foundation\UploadFile\Clients;

use FastDFS;
use Exception;
use SplFileInfo;
use Illuminate\Support\Str;
use QT\Foundation\Contracts\UploadFileClient;

class FastDFSClient implements UploadFileClient
{
    protected $fastDFS;
    protected $tracker;
    protected $storage;
    protected $server;
    protected $config;

    public function __construct($config = [])
    {
        if (!class_exists(FastDFS::class)) {
            throw new Exception('未安装FastDFS扩展');
        }

        $this->config  = $config;
        $this->fastDFS = new FastDFS();
        $this->tracker = $this->fastDFS->tracker_get_connection();
        if (!$this->tracker) {
            throw new Exception('cannot connect to tracker server:[' .
                $this->fastDFS->get_last_error_no() . '] ' .
                $this->fastDFS->get_last_error_info());
        }

        $this->storage = $this->fastDFS->tracker_query_storage_store();
        $this->server  = $this->fastDFS->connect_server($this->storage['ip_addr'], $this->storage['port']);
        if ($this->server === false) {
            throw new Exception('cannot connect to storage server' .
                $this->storage['ip_addr'] . ':' .
                $this->storage['port'] . ' :[' .
                $this->fastDFS->get_last_error_no() . '] ' .
                $this->fastDFS->get_last_error_info());
        }
        $this->storage['sock'] = $this->server['sock'];
    }

    public function upload($filename, $bucket, array $options = []): string
    {
        $file = new SplFileInfo($filename);
        // 检查文件是否可以读取
        if (!$file->isReadable()) {
            throw new Exception('文件不能读取');
        }
        // 上传文件
        $ext  = $file->getExtension();
        $info = $this->storageUploadByFilename(
            $file, $ext, [], $bucket, $this->tracker, $this->storage
        );

        if (!is_array($info)) {
            throw new Exception("Upload error:[{$this->getLastErrorNo()}] {$this->getLastErrorInfo()}");
        }

        $path = "{$info['group_name']}/{$info['filename']}";

        // 返回图片上传后可访问的地址
        if (!isset($this->config['host'])) {
            return $path;
        }

        return "{$this->config['host']}/{$path}";
    }

    public function delete($bucket, $name): bool
    {
        return $this->storageDeleteFile($bucket, $name);
    }

    /**
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $method = Str::snake($method);

        return $this->fastDFS->{$method}(...$parameters);
    }
}
