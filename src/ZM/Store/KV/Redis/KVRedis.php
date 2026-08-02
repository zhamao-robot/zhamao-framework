<?php

declare(strict_types=1);

namespace ZM\Store\KV\Redis;

use Psr\SimpleCache\CacheInterface;
use ZM\Store\KV\KVInterface;

class KVRedis implements KVInterface
{
    private string $pool_name;

    public function __construct(private string $name = '')
    {
        $this->pool_name = config('global.kv.redis_config', 'default');
    }

    public static function open(string $name = ''): CacheInterface
    {
        return new KVRedis($name);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = $redis->get($this->name . ':' . $key);
        if ($ret === false) {
            $ret = $default;
        } else {
            $ret = unserialize($ret);
        }
        RedisPool::pool($this->pool_name)->put($redis);
        return $ret;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = $redis->set($this->name . ':' . $key, serialize($value), $ttl);
        RedisPool::pool($this->pool_name)->put($redis);
        return (bool) $ret;
    }

    public function delete(string $key): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = $redis->del($this->name . ':' . $key);
        RedisPool::pool($this->pool_name)->put($redis);
        return (bool) $ret;
    }

    public function clear(): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        // Redis 的 DEL 命令不支持通配符，这里使用 SCAN 迭代删除所有以 name 开头的键
        // PSR-16 规定 clear() 执行成功即返回 true，即使没有匹配到任何键
        $iterator = null;
        $prefix = $this->name . ':*';
        do {
            $keys = $redis->scan($iterator, $prefix);
            if ($keys === false) {
                break;
            }
            foreach ($keys as $key) {
                $redis->del($key);
            }
        } while ($iterator > 0);
        RedisPool::pool($this->pool_name)->put($redis);
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        foreach ($keys as $key) {
            $value = $redis->get($this->name . ':' . $key);
            if ($value === false) {
                $value = $default;
            } else {
                $value = unserialize($value);
            }
            yield $key => $value;
        }
        RedisPool::pool($this->pool_name)->put($redis);
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = true;
        foreach ($values as $key => $value) {
            $ret = $ret && $redis->set($this->name . ':' . $key, serialize($value), $ttl);
        }
        RedisPool::pool($this->pool_name)->put($redis);
        return $ret;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = true;
        foreach ($keys as $key) {
            $ret = $ret && $redis->del($this->name . ':' . $key);
        }
        RedisPool::pool($this->pool_name)->put($redis);
        return $ret;
    }

    public function has(string $key): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = $redis->exists($this->name . ':' . $key);
        RedisPool::pool($this->pool_name)->put($redis);
        return (bool) $ret;
    }
}
