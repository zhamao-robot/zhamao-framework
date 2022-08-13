<?php

declare(strict_types=1);

namespace ZM\Middleware;

class TimerMiddleware implements MiddlewareInterface, PipelineInterface
{
    public function handle(callable $callback, ...$params)
    {
        $starttime = microtime(true);
        $result = $callback(...$params);
        logger()->info('Pipeline using ' . round((microtime(true) - $starttime) * 1000, 4) . ' ms');
        return $result;
    }
}
