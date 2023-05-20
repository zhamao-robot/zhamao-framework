<?php

declare(strict_types=1);

namespace ZM\Middleware;

use OneBot\Driver\Event\WebSocket\WebSocketCloseEvent;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use ZM\Annotation\Framework\BindEvent;
use ZM\Utils\ConnectionUtil;

class WebSocketFilter implements MiddlewareInterface, PipelineInterface
{
    use MiddlewareArgTrait;
    use NeedAnnotationTrait;

    public function handle(callable $callback, ...$params)
    {
        if (!$this->annotation instanceof BindEvent) {
            return null;
        }
        if (is_a($this->annotation->event_class, WebSocketOpenEvent::class, true)) {
            return $this->filterOpen(container()->get(WebSocketOpenEvent::class)) ? $callback(...$params) : null;
        }
        if (is_a($this->annotation->event_class, WebSocketMessageEvent::class, true)) {
            return $this->filterMessageAndClose(container()->get(WebSocketMessageEvent::class)) ? $callback(...$params) : null;
        }
        if (is_a($this->annotation->event_class, WebSocketCloseEvent::class, true)) {
            return $this->filterMessageAndClose(container()->get(WebSocketCloseEvent::class)) ? $callback(...$params) : null;
        }
        return $callback(...$params);
    }

    private function filterOpen(WebSocketOpenEvent $event): bool
    {
        // 过滤存在 flag 设置的情况
        if (($this->args['flag'] ?? null) !== null && $this->args['flag'] !== $event->getSocketFlag()) {
            return false;
        }
        return true;
    }

    private function filterMessageAndClose(WebSocketMessageEvent|WebSocketCloseEvent $event): bool
    {
        // 过滤存在 flag 设置的情况
        if (($this->args['flag'] ?? null) !== null && $this->args['flag'] !== $event->getSocketFlag()) {
            return false;
        }
        // 过滤连接信息
        // 这里需要考虑一下 ws client 的情况，TODO
        $conn = ConnectionUtil::getConnection($event->getFd());
        foreach ($this->args as $k => $v) {
            if (!isset($conn[$k])) {
                return false;
            }
            if (is_string($v) && $conn[$k] !== $v) {
                return false;
            }
        }
        return true;
    }
}
