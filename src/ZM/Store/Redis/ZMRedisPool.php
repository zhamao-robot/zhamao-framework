<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace ZM\Store\Redis;

use RedisException;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;
use ZM\Console\Console;

class ZMRedisPool
{
    /** @var null|RedisPool */
    public static $pool;

    public static function init($config)
    {
        self::$pool = new RedisPool(
            (new RedisConfig())
                ->withHost($config['host'])
                ->withPort($config['port'])
                ->withAuth($config['auth'])
                ->withDbIndex($config['db_index'])
                ->withTimeout($config['timeout'] ?? 1)
        );
        try {
            $r = self::$pool->get()->ping('123');
            if (strpos(strtolower($r), '123') !== false) {
                Console::debug('成功连接redis连接池！');
            } else {
                var_dump($r);
            }
        } catch (RedisException $e) {
            Console::error(zm_internal_errcode('E00047') . 'Redis init failed! ' . $e->getMessage());
            self::$pool = null;
        }
    }
}
