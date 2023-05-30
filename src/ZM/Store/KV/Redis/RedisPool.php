<?php

declare(strict_types=1);

namespace ZM\Store\KV\Redis;

use OneBot\Driver\Driver;
use OneBot\Driver\Interfaces\PoolInterface;
use OneBot\Driver\Swoole\ObjectPool as SwooleObjectPool;
use OneBot\Driver\Swoole\SwooleDriver;
use OneBot\Driver\Workerman\ObjectPool as WorkermanObjectPool;
use OneBot\Driver\Workerman\WorkermanDriver;

class RedisPool
{
    /**
     * @var array<string, SwooleObjectPool|WorkermanObjectPool> 连接池列表
     */
    public static array $pools = [];

    /**
     * 重新初始化连接池，有时候连不上某个对象时候可以使用，也可以定期调用释放链接
     *
     * @throws RedisException
     */
    public static function resetPools(): void
    {
        // 清空 Redis 连接池
        foreach (self::getAllPools() as $name => $pool) {
            self::destroyPool($name);
        }

        // 读取 Redis 配置文件并创建池
        $redis_conf = config('global.redis');
        foreach ($redis_conf as $name => $conn_conf) {
            if (($conn_conf['enable'] ?? true) !== false) {
                self::create($name, $conn_conf);
            }
        }
    }

    /**
     * @throws RedisException
     */
    public static function create(string $name, array $config): void
    {
        $size = $config['pool_size'] ?? 10;
        self::checkRedisExtension();
        switch (Driver::getActiveDriverClass()) {
            case WorkermanDriver::class:
                self::$pools[$name] = new WorkermanObjectPool($size, ZMRedis::class, $config);
                break;
            case SwooleDriver::class:
                self::$pools[$name] = new SwooleObjectPool($size, ZMRedis::class, $config);
                break;
        }
        /** @var ZMRedis $r */
        $r = self::$pools[$name]->get();
        try {
            /* @phpstan-ignore-next-line */
            $result = $r->ping('123');
            if (str_contains($result, '123')) {
                self::$pools[$name]->put($r);
                logger()->debug('Redis pool ' . $name . ' created');
            }
        } catch (\RedisException $e) {
            self::$pools[$name] = null;
            logger()->error(zm_internal_errcode('E00047') . 'Redis init failed! ' . $e->getMessage());
        }
    }

    /**
     * 获取一个数据库连接池
     *
     * @param string $name 连接池名称
     */
    public static function pool(string $name): PoolInterface
    {
        if (!isset(self::$pools[$name]) && count(self::$pools) !== 1) {
            throw new \RuntimeException("Pool {$name} not found");
        }
        return self::$pools[$name] ?? self::$pools[array_key_first(self::$pools)];
    }

    /**
     * @throws RedisException
     */
    public static function checkRedisExtension(): void
    {
        if (!extension_loaded('redis')) {
            throw new RedisException(zm_internal_errcode('E00029') . '未安装 redis 扩展');
        }
    }

    /**
     * 销毁数据库连接池
     *
     * @param string $name 数据库连接池名称
     */
    public static function destroyPool(string $name)
    {
        unset(self::$pools[$name]);
    }

    /**
     * 获取所有数据库连接池
     *
     * @return PoolInterface[]
     */
    public static function getAllPools(): array
    {
        return self::$pools;
    }
}
