<?php

declare(strict_types=1);

namespace ZM\Context\Trait;

use Choir\Http\HttpFactory;
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
    private null|WebSocketMessageEvent|HttpRequestEvent $base_event;

    /**
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
        self::$echo_id_list[$a->echo] = $a;
        // 调用事件在回复之前的回调
        $handler = new AnnotationHandler(BotAction::class);
        container()->set(Action::class, $a);
        $handler->setRuleCallback(fn (BotAction $act) => $act->action === '' || $act->action === $action && !$act->need_response);
        $handler->handleAll($a);
        // 被阻断时候，就不发送了
        if ($handler->getStatus() === AnnotationHandler::STATUS_INTERRUPTED) {
            return false;
        }

        // 调用机器人连接发送 Action
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
        if (isset($result)) {
            return $result;
        }
        /* TODO: 协程支持
        if (($result ?? false) === true && ($co = Adaptive::getCoroutine()) !== null) {
            return $result ?? false;
        }*/
        throw new OneBot12Exception('No bot connection found.');
    }
}
