<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use Choir\Http\HttpFactory;
use OneBot\Driver\Event\WebSocket\WebSocketCloseEvent;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use OneBot\Util\Singleton;
use ZM\Annotation\AnnotationHandler;
use ZM\Annotation\Framework\BindEvent;
use ZM\Container\ContainerServicesProvider;
use ZM\Utils\ConnectionUtil;

class WSEventListener
{
    use Singleton;

    /**
     * @throws \Throwable
     */
    public function onWebSocketOpen(WebSocketOpenEvent $event): void
    {
        logger()->info('接入连接: ' . $event->getFd());
        // 计数，最多只能接入 1024 个连接，为了适配多进程
        if (!ConnectionUtil::addConnection($event->getFd(), [])) {
            $event->withResponse(HttpFactory::createResponse(503));
            return;
        }
        // 注册容器
        resolve(ContainerServicesProvider::class)->registerServices('connection');

        // 调用注解
        $handler = new AnnotationHandler(BindEvent::class);
        $handler->setRuleCallback(fn ($x) => is_a($x->event_class, WebSocketOpenEvent::class, true));
        $handler->handleAll($event);
    }

    public function onWebSocketMessage(WebSocketMessageEvent $event): void
    {
        // 调用注解
        $handler = new AnnotationHandler(BindEvent::class);
        $handler->setRuleCallback(fn ($x) => is_a($x->event_class, WebSocketMessageEvent::class, true));
        $handler->handleAll($event);
    }

    /**
     * @throws \Throwable
     */
    public function onWebSocketClose(WebSocketCloseEvent $event): void
    {
        logger()->info('关闭连接: ' . $event->getFd());
        // 调用注解
        $handler = new AnnotationHandler(BindEvent::class);
        $handler->setRuleCallback(fn ($x) => is_a($x->event_class, WebSocketCloseEvent::class, true));
        $handler->handleAll($event);

        ConnectionUtil::removeConnection($event->getFd());
        resolve(ContainerServicesProvider::class)->cleanup();
    }
}
