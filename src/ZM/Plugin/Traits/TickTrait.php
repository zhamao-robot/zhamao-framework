<?php

declare(strict_types=1);

namespace ZM\Plugin\Traits;

use ZM\Annotation\Framework\Tick;

trait TickTrait
{
    protected array $ticks = [];

    public function addTimerTick(int $ms, callable $callback, int $worker_id = 0): void
    {
        $tick = new Tick($ms, $worker_id);
        if (is_array($callback)) {
            $tick->class = $callback[0];
            $tick->method = $callback[1];
        } elseif ($callback instanceof \Closure) {
            $tick->method = $callback;
        }
        $this->ticks[] = $tick;
    }

    public function getTimerTicks(): array
    {
        return $this->ticks;
    }
}
