<?php

namespace QT\Foundation\Traits;

use Illuminate\Support\Facades\Redis;

/**
 * 单点登录校验逻辑
 * 
 * @package QT\Foundation\Traits
 */
trait SingleSignOn
{
    /**
     * 获取白名单中的token
     *
     * @param string $token
     * @param string $id
     * @param int    $ttl
     */
    public function getTokenByWhitelist($id)
    {
        return Redis::get($this->getRedisKey($id));
    }

    /**
     * 添加token到白名单
     *
     * @param string $token
     * @param string $id
     * @param int    $ttl
     */
    public function addToWhitelist($token, $id, $ttl)
    {
        // 保存token,以便后续做单点登录校验
        Redis::setEx($this->getRedisKey($id), $ttl * 60, $token);
    }

    /**
     * 将token失效
     *
     * @param string $token
     */
    public function invalidate($id)
    {
        Redis::del($this->getRedisKey($id));
    }

    /**
     * 检查token是否在白名单中
     *
     * @param string $token
     * @return int
     */
    public function inWhitelist($id, $token)
    {
        // 开发模式下不用验证白名单
        if (config('app.debug') === true) {
            return true;
        }
        // 检查token是否与redis中的一致
        return $token === $this->getTokenByWhitelist($id);
    }

    /**
     * @param string|int $id
     * @return string
     */
    protected function getRedisKey($id)
    {
        $table = $this->provider->createModel()->getTable();

        return "token:{$table}:{$id}";
    }
}