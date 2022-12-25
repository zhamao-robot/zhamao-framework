<?php

declare(strict_types=1);

namespace Module\Example;

use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\V12\Object\OneBotEvent;
use ZM\Annotation\Http\Route;
use ZM\Annotation\Middleware\Middleware;
use ZM\Annotation\OneBot\BotEvent;
use ZM\Middleware\TimerMiddleware;

class Hello123
{
    #[Route('/route', request_method: ['GET'])]
    #[Middleware(TimerMiddleware::class)]
    public function route()
    {
        return 'Hello Zhamao！This is the first 3.0 page！';
    }

    #[BotEvent()]
    public function onOBEvent(OneBotEvent $event, WebSocketMessageEvent $messageEvent): void
    {
        logger()->info("收到了 {$event->getType()}.{$event->getDetailType()} 事件");
    }
}
