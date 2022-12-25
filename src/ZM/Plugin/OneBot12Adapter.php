<?php

declare(strict_types=1);

namespace ZM\Plugin;

use Choir\Http\HttpFactory;
use OneBot\Driver\Event\StopException;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use OneBot\V12\Exception\OneBotException;
use OneBot\V12\Object\ActionResponse;
use OneBot\V12\Object\OneBotEvent;
use OneBot\V12\Validator;
use ZM\Annotation\AnnotationHandler;
use ZM\Annotation\AnnotationMap;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\OneBot\BotActionResponse;
use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\BotEvent;
use ZM\Annotation\OneBot\CommandArgument;
use ZM\Container\ContainerRegistrant;
use ZM\Context\BotContext;
use ZM\Utils\ConnectionUtil;

class OneBot12Adapter extends ZMPlugin
{
    public function __construct(string $submodule = '', ?AnnotationParser $parser = null)
    {
        parent::__construct(__DIR__);
        switch ($submodule) {
            case '':
            case 'onebot12':
                // 处理所有 OneBot 12 的反向 WS 握手事件
                $this->addEvent(WebSocketOpenEvent::class, [$this, 'handleWSReverseOpen']);
                $this->addEvent(WebSocketMessageEvent::class, [$this, 'handleWSReverseMessage']);
                // 在 BotEvent 内处理 BotCommand
                // $cmd_event = BotEvent::make(type: 'message', level: 15)->on([$this, 'handleBotCommand']);
                // $this->addBotEvent($cmd_event);
                // 处理和声明所有 BotCommand 下的 CommandArgument
                $parser->addSpecialParser(BotCommand::class, [$this, 'parseBotCommand']);
                // 不需要给列表写入 CommandArgument
                $parser->addSpecialParser(CommandArgument::class, [$this, 'parseCommandArgument']);
                break;
            case 'onebot12-ban-other-ws':
                // 禁止其他类型的 WebSocket 客户端接入
                $this->addEvent(WebSocketOpenEvent::class, [$this, 'handleUnknownWSReverseInput'], 1);
                break;
        }
    }

    /**
     * 将 BotCommand 假设含有 CommandArgument 的话，就注册到参数列表中
     *
     * @param BotCommand $command                 命令对象
     * @param null|array $same_method_annotations 同一个方法的所有注解
     */
    public function parseBotCommand(BotCommand $command, ?array $same_method_annotations = null): ?bool
    {
        if ($same_method_annotations === null) {
            return null;
        }
        foreach ($same_method_annotations as $v) {
            if ($v instanceof CommandArgument) {
                $command->withArgumentObject($v);
            }
        }
        return null;
    }

    /**
     * 忽略解析记录 CommandArgument 注解
     */
    public function parseCommandArgument(): ?bool
    {
        return true;
    }

    /**
     * 调用 BotCommand 注解的方法
     *
     * @param BotEvent   $event BotEvent 事件
     * @param BotContext $ctx   机器人环境上下文
     */
    public function handleBotCommand(BotEvent $event, BotContext $ctx)
    {
        $handler = new AnnotationHandler(BotCommand::class);
        $handler->setReturnCallback(function ($result) use ($ctx) {
            if (is_string($result)) {
                $ctx->reply($result);
                return;
            }
            try {
                Validator::validateMessageSegment($result);
                $ctx->reply($result);
            } catch (\Throwable) {
            }
            if ($ctx->hasReplied()) {
                AnnotationHandler::interrupt();
            }
        });
        // 匹配消息
        $match_result = $this->matchBotCommand($ctx->getEvent());
    }

    /**
     * @throws StopException
     */
    public function handleUnknownWSReverseInput(WebSocketOpenEvent $event)
    {
        // 判断是不是 OneBot 12 反向 WS 连进来的，通过 Sec-WebSocket-Protocol 头
        $line = explode('.', $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol'), 2);
        if ($line[0] !== '12') {
            logger()->warning('不允许接入除 OneBot 12 以外的 WebSocket Client');
            $event->withResponse(HttpFactory::createResponse(403, 'Forbidden'));
            $event->stopPropagation();
        }
    }

    /**
     * 接入和认证反向 WS 的连接
     * @throws StopException
     */
    public function handleWSReverseOpen(WebSocketOpenEvent $event): void
    {
        // 判断是不是 OneBot 12 反向 WS 连进来的，通过 Sec-WebSocket-Protocol 头
        $line = explode('.', $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol'), 2);
        if ($line[0] === '12') {
            logger()->info('检测到 OneBot 12 反向 WS 连接，正在进行认证...');
            // 是 OneBot 12 标准的，准许接入，进行鉴权
            $request = $event->getRequest();
            $info = ['impl' => $line[1] ?? 'unknown'];
            if (($stored_token = $event->getSocketConfig()['access_token'] ?? '') !== '') {
                // 测试 Header
                $token = $request->getHeaderLine('Authorization');
                if ($token === '') {
                    // 测试 Query
                    $token = $request->getQueryParams()['access_token'] ?? '';
                }
                $token = explode('Bearer ', $token);
                if (!isset($token[1]) || $token[1] !== $stored_token) { // 没有 token，鉴权失败
                    logger()->warning('OneBot 12 反向 WS 连接鉴权失败，拒绝接入');
                    $event->withResponse(HttpFactory::createResponse(401, 'Unauthorized'));
                    $event->stopPropagation();
                }
            }
            logger()->info('OneBot 12 反向 WS 连接鉴权成功，接入成功[' . $event->getFd() . ']');
        }
        // 设置 OneBot 相关的东西
        ConnectionUtil::setConnection($event->getFd(), $info ?? []);
    }

    /**
     * 处理 WebSocket 消息
     *
     * @param  WebSocketMessageEvent $event 事件对象
     * @throws OneBotException
     * @throws \Throwable
     */
    public function handleWSReverseMessage(WebSocketMessageEvent $event): void
    {
        // 忽略非 OneBot 12 的消息
        $impl = ConnectionUtil::getConnection($event->getFd())['impl'] ?? null;
        if ($impl === null) {
            return;
        }

        // 解析 Frame 到 UTF-8 JSON
        $body = $event->getFrame()->getData();
        $body = json_decode($body, true);
        if ($body === null) {
            logger()->warning('收到非 JSON 格式的消息，已忽略');
            return;
        }

        if (isset($body['type'], $body['detail_type'])) {
            // 如果含有 type，detail_type 字段，表明是 event
            try {
                $obj = new OneBotEvent($body);
            } catch (OneBotException $e) {
                logger()->debug('收到非 OneBot 12 标准的消息，已忽略');
                return;
            }

            // 绑定容器
            ContainerRegistrant::registerOBEventServices($obj);

            // 调用 BotEvent 事件
            $handler = new AnnotationHandler(BotEvent::class);
            $handler->setRuleCallback(function (BotEvent $event) use ($obj) {
                return ($event->type === null || $event->type === $obj->type)
                    && ($event->sub_type === null || $event->sub_type === $obj->sub_type)
                    && ($event->detail_type === null || $event->detail_type === $obj->detail_type);
            });
            $handler->handleAll($obj);
        } elseif (isset($body['status'], $body['retcode'])) {
            // 如果含有 status，retcode 字段，表明是 action 的 response
            $resp = new ActionResponse();
            $resp->retcode = $body['retcode'];
            $resp->status = $body['status'];
            $resp->message = $body['message'] ?? '';
            $resp->data = $body['data'] ?? null;

            ContainerRegistrant::registerOBActionResponseServices($resp);

            // 调用 BotActionResponse 事件
            $handler = new AnnotationHandler(BotActionResponse::class);
            $handler->setRuleCallback(function (BotActionResponse $event) use ($resp) {
                return $event->retcode === null || $event->retcode === $resp->retcode;
            });
            $handler->handleAll($resp);
        }
    }

    private function matchBotCommand(OneBotEvent $event): array
    {
        $ls = AnnotationMap::$_list[BotCommand::class] ?? [];
        $msg = $event->getMessageString();
        // TODO: 还没写完匹配 BotCommand
        return [];
    }
}
