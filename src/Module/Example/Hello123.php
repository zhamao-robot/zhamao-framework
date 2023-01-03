<?php

declare(strict_types=1);

namespace Module\Example;

use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use ZM\Annotation\Framework\Cron;
use ZM\Annotation\Http\Route;
use ZM\Annotation\Middleware\Middleware;
use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\BotEvent;
use ZM\Annotation\OneBot\CommandArgument;
use ZM\Annotation\OneBot\CommandHelp;
use ZM\Context\BotContext;
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
    public function onOBEvent(\OneBotEvent $event, WebSocketMessageEvent $messageEvent): void
    {
        logger()->info("收到了 {$event->getType()}.{$event->getDetailType()} 事件");
    }

    #[BotCommand('echo', 'echo')]
    #[CommandArgument('text', '要回复的内容', required: true)]
    #[CommandHelp('复读机', '只需要发送 echo+内容 即可自动复读', 'echo 你好   会回复 你好')]
    public function repeat(\OneBotEvent $event, BotContext $context): void
    {
        $context->reply($event->getMessage());
    }

    #[Cron('* * * * *', no_overlap: true)]
    public function logTime(): void
    {
        $time = date('Y-m-d H:i:s');
        logger()->info('我看到时间了，让我写下来');
        Adaptive::sleep(5);
        logger()->info('写好啦，时间是' . $time);
    }

    #[Cron('* * * * *')]
    public function logTime2(): void
    {
        logger()->info('我不需要等，但也不给你看时间');
    }
}
