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
use ZM\Exception\Handler;
use ZM\Utils\ConnectionUtil;

class WSEventListener
{
    use Singleton;

    /**
     * @throws \Throwable
     */
    public function onWebSocketOpen(WebSocketOpenEvent $event): void
    {
        // 计数，最多只能接入 1024 个连接，为了适配多进程
        if (!ConnectionUtil::addConnection($event->getFd(), [])) {
            $event->withResponse(HttpFactory::createResponse(503));
            return;
        }
        // 注册容器
        resolve(ContainerServicesProvider::class)->registerServices('connection');
        container()->instance(WebSocketOpenEvent::class, $event);
        container()->alias(WebSocketOpenEvent::class, 'ws.open.event');

        // 调用注解
        $handler = new AnnotationHandler(BindEvent::class);
        $handler->setRuleCallback(fn ($x) => is_a($x->event_class, WebSocketOpenEvent::class, true));
        $handler->handleAll($event);

        resolve(ContainerServicesProvider::class)->cleanup();
    }

    public function onWebSocketMessage(WebSocketMessageEvent $event): void
    {
        container()->instance(WebSocketMessageEvent::class, $event);
        container()->alias(WebSocketMessageEvent::class, 'ws.message.event');
        // 调用注解
        try {
            $handler = new AnnotationHandler(BindEvent::class);
            $handler->setRuleCallback(fn ($x) => is_a($x->event_class, WebSocketMessageEvent::class, true));
            $handler->handleAll();
        } catch (\Throwable $e) {
            logger()->error("处理 WebSocket 消息时出现异常：{$e->getMessage()}");
            Handler::getInstance()->handle($e);
        } finally {
            resolve(ContainerServicesProvider::class)->cleanup();
        }
    }

    /**
     * @throws \Throwable
     */
    public function onWebSocketClose(WebSocketCloseEvent $event): void
    {
        logger()->info('关闭连接: ' . $event->getFd());
        // 绑定容器
        container()->instance(WebSocketCloseEvent::class, $event);
        container()->alias(WebSocketCloseEvent::class, 'ws.close.event');
        // 调用注解
        $handler = new AnnotationHandler(BindEvent::class);
        $handler->setRuleCallback(fn ($x) => is_a($x->event_class, WebSocketCloseEvent::class, true));
        $handler->handleAll($event);

        ConnectionUtil::removeConnection($event->getFd());
        resolve(ContainerServicesProvider::class)->cleanup();
    }
}
