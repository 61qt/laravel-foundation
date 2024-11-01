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
     * @param string|int $id
     * @return string|null
     */
    public function getTokenByWhitelist(string|int $id): ?string
    {
        return Redis::get($this->getRedisKey($id));
    }

    /**
     * 添加token到白名单
     *
     * @param string $token
     * @param int|string $id
     * @param int $ttl
     * @return void
     */
    public function addToWhitelist(string $token, int|string $id, int $ttl)
    {
        // 保存token,以便后续做单点登录校验
        Redis::setEx($this->getRedisKey($id), $ttl * 60, $token);
    }

    /**
     * 删除token
     *
     * @param string|int $id
     * @return void
     */
    public function invalidate(string|int $id)
    {
        Redis::del($this->getRedisKey($id));
    }

    /**
     * 检查token是否在白名单中
     *
     * @param string|int $id
     * @param string $token
     * @return bool
     */
    public function inWhitelist(string|int $id, string $token): bool
    {
        // 开发模式下不用验证白名单
        if (isDebug()) {
            return true;
        }

        // 检查token是否与redis中的一致
        return $token === $this->getTokenByWhitelist($id);
    }

    /**
     * 获取redis的key
     *
     * @param string|int $id
     * @return string
     */
    protected function getRedisKey(string|int $id): string
    {
        $table = $this->provider->createModel()->getTable();

        return "token:{$table}:{$id}";
    }
}
