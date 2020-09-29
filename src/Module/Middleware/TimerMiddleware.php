<?php

namespace Module\Middleware;

use ZM\Annotation\Http\After;
use ZM\Annotation\Http\Before;
use ZM\Annotation\Http\MiddlewareClass;
use ZM\Console\Console;
use ZM\Http\MiddlewareInterface;

/**
 * Class TimerMiddleware
 * 示例中间件：用于统计路由函数运行时间用的
 * @package Module\Middleware
 * @MiddlewareClass("timer")
 */
class TimerMiddleware implements MiddlewareInterface
{
    private $starttime;

    /**
     * @Before()
     * @return bool
     */
    public function onBefore() {
        $this->starttime = microtime(true);
        return true;
    }

    /**
     * @After()
     */
    public function onAfter() {
        Console::info("Using " . round((microtime(true) - $this->starttime) * 1000, 2) . " ms.");
    }
}
