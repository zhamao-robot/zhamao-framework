<?php

declare(strict_types=1);

namespace ZM\Container;

use Choir\WebSocket\FrameInterface;
use DI;
use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Driver\Event\WebSocket\WebSocketCloseEvent;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use OneBot\V12\Object\ActionResponse;
use OneBot\V12\Object\OneBotEvent;
use Psr\Http\Message\ServerRequestInterface;
use ZM\Context\BotContext;

class ContainerRegistrant
{
    /**
     * 应在收到 OneBot 事件时调用
     */
    public static function registerOBEventServices(OneBotEvent $event, string $bot_context = BotContext::class): void
    {
        self::addServices([
            OneBotEvent::class => $event,
            'bot.event' => DI\get(OneBotEvent::class),
        ]);

        // 不用依赖注入可能会更好一点，而且方便其他开发者排查问题（因为挺多开发者在非机器人事件里面用 bot() 的，会让依赖注入报错，而且他们自己也看不懂
        // 而且我想让 BotContext 对象成为无状态无数据的对象，一切东西都从 container 和 BotMap 获取，它就是用作调用方法而已
        /*
        if (isset($event->self['platform'])) {
            self::addServices([
                BotContext::class => DI\autowire($bot_context)->constructor(
                    $event->self['user_id'] ?? '',
                    $event->self['platform'],
                ),
            ]);
        }
        */
    }

    /**
     * 应在收到 OneBot 动作响应时调用
     */
    public static function registerOBActionResponseServices(ActionResponse $response): void
    {
        self::addServices([
            ActionResponse::class => $response,
            'bot.action.response' => DI\get(ActionResponse::class),
        ]);
    }

    /**
     * 应在收到 HTTP 请求时调用
     */
    public static function registerHttpRequestServices(HttpRequestEvent $event): void
    {
        self::addServices([
            HttpRequestEvent::class => $event,
            'http.request.event' => DI\get(HttpRequestEvent::class),
            ServerRequestInterface::class => fn () => $event->getRequest(),
            'http.request' => DI\get(ServerRequestInterface::class),
        ]);
    }

    /**
     * 应在收到 WebSocket 连接时调用
     */
    public static function registerWSOpenServices(WebSocketOpenEvent $event): void
    {
        self::addServices([
            WebSocketOpenEvent::class => $event,
            'ws.open.event' => DI\get(WebSocketOpenEvent::class),
        ]);
    }

    /**
     * 应在收到 WebSocket 消息时调用
     */
    public static function registerWSMessageServices(WebSocketMessageEvent $event): void
    {
        self::addServices([
            WebSocketMessageEvent::class => $event,
            'ws.message.event' => DI\get(WebSocketMessageEvent::class),
            FrameInterface::class => $event->getFrame(),
            'ws.message.frame' => DI\get(FrameInterface::class),
        ]);
    }

    /**
     * 应在收到 WebSocket 关闭时调用
     */
    public static function registerWSCloseServices(WebSocketCloseEvent $event): void
    {
        self::addServices([
            WebSocketCloseEvent::class => $event,
            'ws.close.event' => DI\get(WebSocketCloseEvent::class),
        ]);
    }

    private static function addServices(array $services): void
    {
        foreach ($services as $name => $service) {
            ContainerHolder::getEventContainer()->set($name, $service);
        }
    }
}
