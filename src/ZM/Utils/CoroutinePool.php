<?php

declare(strict_types=1);

namespace ZM\Utils;

use Swoole\Coroutine;

class CoroutinePool
{
    private static $cids = [];

    private static $default_size = 30;

    private static $sizes = [];

    private static $yields = [];

    public static function go(callable $func, $name = 'default')
    {
        if (!isset(self::$cids[$name])) {
            self::$cids[$name] = [];
        }
        if (count(self::$cids[$name]) >= (self::$sizes[$name] ?? self::$default_size)) {
            self::$yields[] = Coroutine::getCid();
            Coroutine::suspend();
        }
        go(function () use ($func, $name) {
            self::$cids[$name][] = Coroutine::getCid();
            // Console::debug("正在执行协程，当前协程池中有 " . count(self::$cids[$name]) . " 个正在运行的协程: ".implode(", ", self::$cids[$name]));
            $func();
            self::checkCids($name);
        });
    }

    public static function defaultSize(int $size)
    {
        self::$default_size = $size;
    }

    public static function setSize($name, int $size)
    {
        self::$sizes[$name] = $size;
    }

    public static function getRunningCoroutineCount($name = 'default')
    {
        return count(self::$cids[$name]);
    }

    private static function checkCids($name)
    {
        if (in_array(Coroutine::getCid(), self::$cids[$name])) {
            $a = array_search(Coroutine::getCid(), self::$cids[$name]);
            array_splice(self::$cids[$name], $a, 1);
            $r = array_shift(self::$yields);
            if ($r !== null) {
                Coroutine::resume($r);
            }
        }
    }
}
