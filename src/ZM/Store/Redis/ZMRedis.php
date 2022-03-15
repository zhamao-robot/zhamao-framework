<?php

/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace ZM\Store\Redis;

use Redis;
use ZM\Exception\NotInitializedException;

class ZMRedis
{
    private $conn;

    /**
     * ZMRedis constructor.
     * @throws NotInitializedException
     */
    public function __construct()
    {
        if (ZMRedisPool::$pool === null) {
            throw new NotInitializedException('Redis pool is not initialized.');
        }
        $this->conn = ZMRedisPool::$pool->get();
    }

    public function __destruct()
    {
        if (isset($this->conn->wasted)) {
            ZMRedisPool::$pool->put(null);
        } else {
            ZMRedisPool::$pool->put($this->conn);
        }
    }

    /**
     * @throws NotInitializedException
     * @return mixed
     */
    public static function call(callable $callable)
    {
        if (ZMRedisPool::$pool === null) {
            throw new NotInitializedException('Redis pool is not initialized.');
        }
        $r = ZMRedisPool::$pool->get();
        $result = $callable($r);
        if (isset($r->wasted)) {
            ZMRedisPool::$pool->put(null);
        } else {
            ZMRedisPool::$pool->put($r);
        }
        return $result;
    }

    public function get(): Redis
    {
        return $this->conn;
    }
}
