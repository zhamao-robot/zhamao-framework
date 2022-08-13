<?php

declare(strict_types=1);

namespace ZM\Middleware;

class TimerMiddleware implements MiddlewareInterface
{
    /** @var float */
    private $starttime = 0;

    public function __construct()
    {
        middleware()->registerBefore(static::class, [$this, 'onBefore']);
        middleware()->registerAfter(static::class, [$this, 'onAfter']);
    }

    public function onBefore(): bool
    {
        $this->starttime = microtime(true);
        return true;
    }

    public function onAfter()
    {
        logger()->info('Using ' . round((microtime(true) - $this->starttime) * 1000, 4) . ' ms');
    }
}
