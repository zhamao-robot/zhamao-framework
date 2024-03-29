<?php

declare(strict_types=1);

namespace ZM\Event;

use OneBot\Driver\Interfaces\SortedProviderInterface;
use OneBot\Util\Singleton;

class EventProvider implements SortedProviderInterface
{
    use Singleton;

    /**
     * @var array<string, array<array<int, callable>>> 已注册的事件监听器
     */
    private static array $_events = [];

    /** @var array @phpstan-ignore-next-line */
    private static array $_event_map = [];

    /**
     * 添加事件监听器
     *
     * @param object|string $event    事件名称
     * @param callable      $callback 事件回调
     * @param int           $level    事件等级
     */
    public function addEventListener($event, callable $callback, int $level = 20)
    {
        if (is_object($event)) { // 传入对象时必须带 class 和 method 属性，这时将忽略 callback 参数
            if (property_exists($event, 'class') && property_exists($event, 'method')) {
                self::$_events[$event::class][] = [$level, [resolve($event->class), $event->method]];
                self::$_event_map[$event->class][$event->method][] = $event;
            } elseif (is_array($callback) && is_object($callback[0] ?? '') && is_string($callback[1] ?? null)) {
                // 如果没有上面两个属性，则可能是回调函数是一个数组，如果是这样，则可以直接使用回调函数
                self::$_events[$event::class][] = [$level, $callback];
                self::$_event_map[$callback[0]::class][$callback[1]][] = $event;
                $event->class = $callback[0]::class;
                $event->method = $callback[1];
            }
            $this->sortEvents($event::class);
        } elseif (is_string($event)) {
            self::$_events[$event][] = [$level, $callback];
            $this->sortEvents($event);
        } else {
            logger()->error('传入了错误的对象');
        }
    }

    /**
     * 获取事件监听器
     *
     * @param  string          $event_name 事件名称
     * @return array<callable>
     */
    public function getEventListeners(string $event_name): array
    {
        return self::$_events[$event_name] ?? [];
    }

    /**
     * 获取事件监听器
     *
     * @param  object             $event 事件对象
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        return $this->getEventListeners(method_exists($event, 'getName') ? $event->getName() : $event::class);
    }

    /**
     * 排序事件
     */
    private function sortEvents(string|\Stringable $name)
    {
        usort(self::$_events[$name], fn ($a, $b) => $a[0] <= $b[0] ? 1 : -1);
    }
}
