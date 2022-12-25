<?php

declare(strict_types=1);

namespace ZM\Container;

use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Driver\Event\WebSocket\WebSocketCloseEvent;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;

class ContainerBindingListener
{
    private static array $events = [
        WebSocketOpenEvent::class,
        WebSocketMessageEvent::class,
        WebSocketCloseEvent::class,

        HttpRequestEvent::class,
    ];

    public static function listenForEvents(): void
    {
        // 监听感兴趣的事件，方便做容器初始化和销毁
        foreach (self::$events as $event) {
            ob_event_provider()->addEventListener($event, [self::class, 'callback'], PHP_INT_MAX - 100);
            ob_event_provider()->addEventListener($event, [self::class, 'cleanCallback'], PHP_INT_MIN + 100);
        }
    }

    public static function callback(): void
    {
    }

    public static function cleanCallback(): void
    {
        ContainerHolder::clearEventContainer();
    }
}
