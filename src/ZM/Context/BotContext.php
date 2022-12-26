<?php

declare(strict_types=1);

namespace ZM\Context;

use Choir\Http\HttpFactory;
use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\Util\Utils;
use OneBot\V12\Object\Action;
use OneBot\V12\Object\MessageSegment;
use OneBot\V12\Object\OneBotEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ZM\Annotation\AnnotationHandler;
use ZM\Annotation\OneBot\BotAction;
use ZM\Exception\OneBot12Exception;
use ZM\Utils\MessageUtil;

class BotContext implements ContextInterface
{
    private static array $echo_id_list = [];

    private array $self;

    private array $params = [];

    private bool $replied = false;

    public function __construct(string $bot_id, string $platform)
    {
        $this->self = ['user_id' => $bot_id, 'platform' => $platform];
    }

    public function getEvent(): OneBotEvent
    {
        return container()->get('bot.event');
    }

    /**
     * 快速回复机器人消息文本
     *
     * @param  array|MessageSegment|string|\Stringable $message 消息内容、消息段或消息段数组
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws OneBot12Exception
     * @throws \Throwable
     */
    public function reply(\Stringable|MessageSegment|array|string $message)
    {
        if (container()->has('bot.event')) {
            // 这里直接使用当前上下文的事件里面的参数，不再重新挨个获取怎么发消息的参数
            /** @var OneBotEvent $event */
            $event = container()->get('bot.event');

            // reply 的条件是必须 type=message
            if ($event->getType() !== 'message') {
                throw new OneBot12Exception('bot()->reply() can only be used in message event.');
            }
            $msg = (is_string($message) ? [new MessageSegment('text', ['text' => $message])] : ($message instanceof MessageSegment ? [$message] : $message));
            $this->replied = true;
            return $this->sendMessage($msg, $event->detail_type, $event->jsonSerialize());
        }
        throw new OneBot12Exception('bot()->reply() can only be used in message event.');
    }

    /**
     * 返回是否已经调用过回复了
     */
    public function hasReplied(): bool
    {
        return $this->replied;
    }

    /**
     * 获取其他机器人的上下文操作对象
     *
     * @param  string $bot_id   机器人的 self.user_id 对应的 ID
     * @param  string $platform 机器人的 self.platform 对应的 platform
     * @return $this
     */
    public function getBot(string $bot_id, string $platform = ''): BotContext
    {
        // TODO: 完善多机器人支持
        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function sendMessage(\Stringable|array|MessageSegment|string $message, string $detail_type, array $params = [])
    {
        $message = MessageUtil::convertToArr($message);
        $params['message'] = $message;
        $params['detail_type'] = $detail_type;
        return $this->sendAction(Utils::camelToSeparator(__FUNCTION__), $params, $this->self);
    }

    /**
     * 设置该消息下解析出来的参数列表
     *
     * @param array $params 参数列表
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * 获取单个参数值，不存在则返回 null
     *
     * @param int|string $name 参数名称或索引
     */
    public function getParam(string|int $name): mixed
    {
        return $this->params[$name] ?? null;
    }

    public function getParamString(string|int $name): ?string
    {
        return MessageUtil::getAltMessage($this->params[$name] ?? null);
    }

    /**
     * 获取所有参数
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getEchoAction(mixed $echo): ?Action
    {
        return self::$echo_id_list[$echo] ?? null;
    }

    /**
     * @throws \Throwable
     */
    private function sendAction(string $action, array $params = [], ?array $self = null)
    {
        // 声明 Action 对象
        $a = new Action($action, $params, ob_uuidgen(), $self);
        self::$echo_id_list[$a->echo] = $a;
        // 调用事件在回复之前的回调
        $handler = new AnnotationHandler(BotAction::class);
        $handler->setRuleCallback(fn (BotAction $act) => $act->action === $action && !$act->need_response);
        $handler->handleAll($a);
        // 被阻断时候，就不发送了
        if ($handler->getStatus() === AnnotationHandler::STATUS_INTERRUPTED) {
            return false;
        }

        // 调用机器人连接发送 Action
        if (container()->has('ws.message.event')) {
            /** @var WebSocketMessageEvent $ws */
            $ws = container()->get('ws.message.event');
            return $ws->send(json_encode($a->jsonSerialize()));
        }
        // 如果是 HTTP WebHook 的形式，那么直接调用 Response
        if (container()->has('http.request.event')) {
            /** @var HttpRequestEvent $event */
            $event = container()->get('http.request.event');
            $response = HttpFactory::createResponse(headers: ['Content-Type' => 'application/json'], body: json_encode([$a->jsonSerialize()]));
            $event->withResponse($response);
            return true;
        }
        throw new OneBot12Exception('No bot connection found.');
    }
}
