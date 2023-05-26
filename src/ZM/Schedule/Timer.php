<?php

declare(strict_types=1);

namespace ZM\Schedule;

use OneBot\Driver\Coroutine\Adaptive;
use ZM\Annotation\Framework\Tick;
use ZM\Framework;

class Timer
{
    public static function tick(int $ms, callable $callback, int $times = 0): int
    {
        return Framework::getInstance()->getDriver()->getEventLoop()->addTimer(
            $ms,
            fn (...$params) => Adaptive::getCoroutine() !== null ? Adaptive::getCoroutine()->create($callback, ...$params) : $callback(...$params),
            $times
        );
    }

    public static function after(int $ms, callable $callback): int
    {
        return Framework::getInstance()->getDriver()->getEventLoop()->addTimer(
            $ms,
            fn (...$params) => Adaptive::getCoroutine() !== null ? Adaptive::getCoroutine()->create($callback, ...$params) : $callback(...$params)
        );
    }

    public static function del(int $timer_id): void
    {
        Framework::getInstance()->getDriver()->getEventLoop()->clearTimer($timer_id);
    }

    public static function registerTick(Tick $v): void
    {
        if ($v->class !== '' && $v->method !== '') {
            self::tick($v->tick_ms, [resolve($v->class), $v->method]);
        } elseif (is_callable($v->method)) {
            self::tick($v->tick_ms, $v->method);
        } else {
            logger()->warning('注册的 Tick 定时器回调函数错误');
        }
    }
}
