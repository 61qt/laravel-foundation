<?php 

namespace QT\Foundation\Contracts;

interface UploadFileClient 
{
    /**
     * 上传文件
     * 
     * @param $filename
     * @param $bucket
     * @param $options
     * @return url
     */
    public function upload($filename, $bucket, array $options = []) : string;

    /**
     * 删除远程文件
     * 
     * @param $bucket
     * @param $name
     */
    public function delete($bucket, $name) : bool;
}