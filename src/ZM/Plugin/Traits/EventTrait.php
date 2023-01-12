<?php

namespace ZM\Plugin\Traits;

trait EventTrait
{
    /** @var array 全局的事件列表 */
    protected array $events = [];

    /**
     * 添加一个框架底层的事件
     */
    public function addEvent(string $event_name, callable $callback, int $level = 20): void
    {
        $this->events[] = [$event_name, $callback, $level];
    }

    /**
     * @internal
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
