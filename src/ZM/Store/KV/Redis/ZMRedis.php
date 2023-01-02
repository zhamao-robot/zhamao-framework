<?php

declare(strict_types=1);

namespace ZM\Store\KV\Redis;

class ZMRedis extends \Redis
{
    /**
     * @throws \RedisException
     */
    public function __construct(array $config)
    {
        parent::__construct();
        $this->connect($config['host'], $config['port'], $config['timeout'] ?? 0);
        if (!empty($config['auth'])) {
            $this->auth($config['auth']);
        }
        $this->select($config['index']);
    }

    /**
     * @throws \RedisException
     */
    public function __destruct()
    {
        $this->close();
    }
}
