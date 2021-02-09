<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Store\Redis;

use Redis;
use ZM\Exception\NotInitializedException;

class ZMRedis
{
    private $conn;

    /**
     * @param callable $callable
     * @return mixed
     * @throws NotInitializedException
     */
    public static function call(callable $callable) {
        if (ZMRedisPool::$pool === null) throw new NotInitializedException("Redis pool is not initialized.");
        $r = ZMRedisPool::$pool->get();
        $result = $callable($r);
        if (isset($r->wasted)) ZMRedisPool::$pool->put(null);
        else ZMRedisPool::$pool->put($r);
        return $result;
    }

    /**
     * ZMRedis constructor.
     * @throws NotInitializedException
     */
    public function __construct() {
        if (ZMRedisPool::$pool === null) throw new NotInitializedException("Redis pool is not initialized.");
        $this->conn = ZMRedisPool::$pool->get();
    }

    /**
     * @return Redis
     */
    public function get() {
        return $this->conn;
    }

    public function __destruct() {
        if (isset($this->conn->wasted)) ZMRedisPool::$pool->put(null);
        else ZMRedisPool::$pool->put($this->conn);
    }
}
