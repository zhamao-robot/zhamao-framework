<?php

declare(strict_types=1);

namespace Module\Example;

use Choir\Http\ServerRequest;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use ZM\Annotation\Framework\BindEvent;
use ZM\Annotation\Http\Route;
use ZM\Annotation\Middleware\Middleware;
use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\CommandArgument;
use ZM\Annotation\OneBot\CommandHelp;
use ZM\Context\BotContext;
use ZM\Middleware\TimerMiddleware;

class Hello123
{
    #[BindEvent(WebSocketMessageEvent::class, level: 5000)]
    public function onMessage(WebSocketMessageEvent $event)
    {
        $Data = json_decode($event->getFrame()->getData(), true);
    }

    #[Route('/route', request_method: ['GET'])]
    #[Route('/route/{id}', request_method: ['GET'])]
    #[Middleware(TimerMiddleware::class)]
    public function route(array $params, ServerRequest $request, \HttpRequestEvent $event, BotContext $ctx)
    {
        // 目前因内部实现限制，路由方法的参数必须按照这个顺序定义，可以省略，但是不能乱序
        // 如果希望获取其他依赖，可以在现有参数后面继续添加
        return 'Hello Zhamao！This is the first 3.0 page！' . ($params['id'] ?? '');
    }

    #[BotCommand('echo', 'echo')]
    #[CommandArgument('text', '要回复的内容', required: true)]
    #[CommandHelp('复读机', '只需要发送 echo+内容 即可自动复读', 'echo 你好   会回复 你好')]
    public function repeat(\OneBotEvent $event, BotContext $context): void
    {
        $context->reply($event->getMessage());
    }
}
