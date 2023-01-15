<?php

declare(strict_types=1);

namespace ZM\Context\Trait;

use Choir\Http\HttpFactory;
use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\Util\Utils;
use OneBot\V12\Object\Action;
use OneBot\V12\Object\ActionResponse;
use OneBot\V12\Object\MessageSegment;
use ZM\Annotation\AnnotationHandler;
use ZM\Annotation\OneBot\BotAction;
use ZM\Exception\OneBot12Exception;
use ZM\Utils\MessageUtil;

trait BotActionTrait
{
    /**
     * @var array<string, int> 一个记录 echo 对应协程 ID 的列表，用于恢复协程
     */
    protected static array $coroutine_list = [];

    protected null|WebSocketMessageEvent|HttpRequestEvent $base_event;

    /**
     * @internal 只允许内部调用
     * @param ActionResponse $response 尝试调用看看有没有协程等待的
     */
    public static function tryResume(ActionResponse $response): void
    {
        if (($co = Adaptive::getCoroutine()) !== null && isset(static::$coroutine_list[$response->echo ?? ''])) {
            $co->resume(static::$coroutine_list[$response->echo ?? ''], $response);
        }
    }

    /**
     * 发送一条机器人消息
     *
     * @param  array|MessageSegment|string|\Stringable $message 消息内容，可以是消息段、字符串
     * @throws \Throwable
     */
    public function sendMessage(\Stringable|array|MessageSegment|string $message, string $detail_type, array $params = []): ActionResponse|bool
    {
        $message = MessageUtil::convertToArr($message);
        $params['message'] = $message;
        $params['detail_type'] = $detail_type;
        return $this->sendAction(Utils::camelToSeparator(__FUNCTION__), $params, $this->self);
    }

    /**
     * 发送机器人动作
     *
     * @throws \Throwable
     */
    public function sendAction(string $action, array $params = [], ?array $self = null): bool|ActionResponse
    {
        // 声明 Action 对象
        $a = new Action($action, $params, ob_uuidgen(), $self);
        // 调用事件在回复之前的回调
        $handler = new AnnotationHandler(BotAction::class);
        container()->set(Action::class, $a);
        $handler->setRuleCallback(fn (BotAction $act) => $act->action === '' || $act->action === $action && !$act->need_response);
        $handler->handleAll($a);
        // 被阻断时候，就不发送了
        if ($handler->getStatus() === AnnotationHandler::STATUS_INTERRUPTED) {
            return false;
        }

        // 调用机器人连接发送 Action，首先试试看是不是 WebSocket
        if ($this->base_event instanceof WebSocketMessageEvent) {
            $result = $this->base_event->send(json_encode($a->jsonSerialize()));
        }
        if (!isset($result) && container()->has('ws.message.event')) {
            $result = container()->get('ws.message.event')->send(json_encode($a->jsonSerialize()));
        }
        // 如果是 HTTP WebHook 的形式，那么直接调用 Response
        if (!isset($result) && $this->base_event instanceof HttpRequestEvent) {
            $response = HttpFactory::createResponse(headers: ['Content-Type' => 'application/json'], body: json_encode([$a->jsonSerialize()]));
            $this->base_event->withResponse($response);
            $result = true;
        }
        if (!isset($result) && container()->has('http.request.event')) {
            $response = HttpFactory::createResponse(headers: ['Content-Type' => 'application/json'], body: json_encode([$a->jsonSerialize()]));
            container()->get('http.request.event')->withResponse($response);
            $result = true;
        }
        // 如果开启了协程，并且成功发送，那就进入协程等待，挂起等待结果返回一个 ActionResponse 对象
        if (($result ?? false) === true && ($co = Adaptive::getCoroutine()) !== null) {
            static::$coroutine_list[$a->echo] = $co->getCid();
            $response = $co->suspend();
            if ($response instanceof ActionResponse) {
                return $response;
            }
            return false;
        }
        if (isset($result)) {
            return $result;
        }
        // 到这里表明你调用时候不在 WS 或 HTTP 上下文
        throw new OneBot12Exception('No bot connection found.');
    }
}
