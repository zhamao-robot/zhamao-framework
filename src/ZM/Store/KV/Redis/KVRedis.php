<?php

declare(strict_types=1);

namespace ZM\Store\KV\Redis;

use Psr\SimpleCache\CacheInterface;
use ZM\Store\KV\KVInterface;

class KVRedis implements CacheInterface, KVInterface
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

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = $redis->set($this->name . ':' . $key, serialize($value), $ttl);
        RedisPool::pool($this->pool_name)->put($redis);
        return (bool) $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = $redis->del($this->name . ':' . $key);
        RedisPool::pool($this->pool_name)->put($redis);
        return (bool) $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = $redis->del($this->name . ':*');
        RedisPool::pool($this->pool_name)->put($redis);
        return (bool) $ret;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        /** @var ZMRedis $redis */
        $redis = RedisPool::pool($this->pool_name)->get();
        $ret = $redis->exists($this->name . ':' . $key);
        RedisPool::pool($this->pool_name)->put($redis);
        return (bool) $ret;
    }
}
