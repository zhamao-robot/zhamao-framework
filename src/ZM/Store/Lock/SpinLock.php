<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace ZM\Store\Lock;

use Swoole\Table;

class SpinLock
{
    /** @var null|Table */
    private static $kv_lock;

    private static $delay = 1;

    public static function init($key_cnt, $delay = 1)
    {
        self::$kv_lock = new Table($key_cnt, 0.7);
        self::$delay = $delay;
        self::$kv_lock->column('lock_num', Table::TYPE_INT, 8);
        return self::$kv_lock->create();
    }

    public static function lock(string $key)
    {
        while (($r = self::$kv_lock->incr($key, 'lock_num')) > 1) { // 此资源已经被锁上了
            usleep(self::$delay * 1000);
        }
    }

    public static function tryLock(string $key): bool
    {
        if (($r = self::$kv_lock->incr($key, 'lock_num')) > 1) {
            return false;
        }
        return true;
    }

    public static function unlock(string $key)
    {
        return self::$kv_lock->set($key, ['lock_num' => 0]);
    }

    public static function transaction(string $key, callable $function)
    {
        SpinLock::lock($key);
        $function();
        SpinLock::unlock($key);
    }
}
