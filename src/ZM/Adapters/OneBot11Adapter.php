<?php

declare(strict_types=1);

namespace ZM\Adapters;

use Swoole\WebSocket\Frame;
use ZM\Annotation\CQ\CQAfter;
use ZM\Annotation\CQ\CQAPIResponse;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQMessage;
use ZM\Annotation\CQ\CQMetaEvent;
use ZM\Annotation\CQ\CQNotice;
use ZM\Annotation\CQ\CQRequest;
use ZM\Config\ZMConfig;
use ZM\Context\ContextInterface;
use ZM\Event\EventDispatcher;
use ZM\Exception\WaitTimeoutException;
use ZM\Utils\CoMessage;
use ZM\Utils\MessageUtil;

class OneBot11Adapter implements AdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'onebot';
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        return '11';
    }

    /**
     * {@inheritDoc}
     */
    public function handleIncomingRequest(Frame $frame, ContextInterface $context): void
    {
        $data = json_decode($frame->data, true);

        // 将数据存入协程参数中
        set_coroutine_params(compact('data'));

        try {
            // 事件类型不存在，代表为非法请求或 API 响应
            if (!isset($data['post_type'])) {
                if (isset($data['echo']) && CoMessage::resumeByWS()) {
                    EventDispatcher::interrupt();
                }
                $this->handleAPIResponse($data, $context);
                return;
            }

            if ($data['post_type'] !== 'meta_event') {
                $before_result = $this->handleBeforeEvent($data, 'pre');
                if ($before_result->store === 'block') {
                    EventDispatcher::interrupt();
                }
            }

            if (CoMessage::resumeByWS()) {
                EventDispatcher::interrupt();
            }

            if ($data['post_type'] !== 'meta_event') {
                $before_result = $this->handleBeforeEvent($data, 'post');
                if ($before_result->store === 'block') {
                    EventDispatcher::interrupt();
                }
            }

            switch ($data['post_type']) {
                case 'message':
                    $this->handleMessageEvent($data, $context);
                    break;
                case 'meta_event':
                    $this->handleMetaEvent($data, $context);
                    break;
                case 'notice':
                    $this->handleNoticeEvent($data, $context);
                    break;
                case 'request':
                    $this->handleRequestEvent($data, $context);
                    break;
            }

            if ($data['post_type'] !== 'meta_event') {
                $before_result = $this->handleAfterEvent($data);
                if ($before_result->store === 'block') {
                    EventDispatcher::interrupt();
                }
            }
        } catch (WaitTimeoutException $e) {
            $e->module->finalReply($e->getMessage());
        } finally {
            if (isset($data['post_type']) && $data['post_type'] !== 'meta_event') {
                $before_result = $this->handleAfterEvent($data);
                if ($before_result->store === 'block') {
                    EventDispatcher::interrupt();
                }
            }
        }
    }

    /**
     * 处理 API 响应
     *
     * @param array            $data    数据
     * @param ContextInterface $context 上下文
     */
    private function handleAPIResponse(array $data, ContextInterface $context): void
    {
        set_coroutine_params(['cq_response' => $data]);
        $dispatcher = new EventDispatcher(CQAPIResponse::class);
        $dispatcher->setRuleFunction(function (CQAPIResponse $event) use ($context) {
            return $event->retcode === $context->getCQResponse()['retcode'];
        });
        $dispatcher->dispatchEvents($data);
    }

    /**
     * 处理消息事件
     *
     * @param array            $data    消息数据
     * @param ContextInterface $context 上下文
     */
    private function handleMessageEvent(array $data, ContextInterface $context): void
    {
        // 分发 CQCommand 事件
        $dispatcher = new EventDispatcher(CQCommand::class);
        // 设定返回值处理函数
        $dispatcher->setReturnFunction(function ($result) use ($context) {
            if (is_string($result)) {
                $context->reply($result);
            }
            if ($context->getCache('has_reply') === true) {
                EventDispatcher::interrupt();
            }
        });

        $message = $data['message'];

        // 将消息段数组转换为消息字符串
        if (is_array($message)) {
            $message = MessageUtil::arrayToStr($message);
        }

        // 匹配命令
        $match_result = MessageUtil::matchCommand($message, $context->getData());

        if ($match_result->status) {
            $matches = $match_result->match;

            $input_arguments = MessageUtil::checkArguments($match_result->object->class, $match_result->object->method, $matches);
            if (!empty($matches)) {
                $context->setCache('match', $matches);
            }

            $dispatcher->dispatchEvent($match_result->object, null, ...$input_arguments);

            // 处理命令返回结果
            if (is_string($dispatcher->store)) {
                $context->reply($dispatcher->store);
            }

            if ($context->getCache('has_reply') === true) {
                $policy = ZMConfig::get('global', 'onebot')['message_command_policy'] ?? 'interrupt';
                switch ($policy) {
                    case 'interrupt':
                        EventDispatcher::interrupt();
                    // no break
                    case 'continue':
                        break;
                    default:
                        throw new \Exception('未知的消息命令策略：' . $policy);
                }
            }
        }

        // 分发 CQMessage 事件
        $dispatcher = new EventDispatcher(CQMessage::class);
        // 设定匹配规则函数
        $dispatcher->setRuleFunction(function (CQMessage $event) use ($context) {
            return ($event->message === '' || ($event->message === $context->getMessage()))
                && ($event->user_id === 0 || ($event->user_id === $context->getUserId()))
                && ($event->group_id === 0 || ($event->group_id === ($context->getGroupId() ?? 0)))
                && ($event->message_type === '' || ($event->message_type === $context->getMessageType()))
                && ($event->raw_message === '' || ($event->raw_message === $context->getData()['raw_message']));
        });
        // 设定返回值处理函数
        $dispatcher->setReturnFunction(function ($result) use ($context) {
            if (is_string($result)) {
                $context->reply($result);
            }
        });

        $dispatcher->dispatchEvents($context->getMessage());
    }

    /**
     * 处理元事件
     *
     * @param array            $data    消息数据
     * @param ContextInterface $context 上下文
     */
    private function handleMetaEvent(array $data, ContextInterface $context): void
    {
        $dispatcher = new EventDispatcher(CQMetaEvent::class);
        // 设定匹配规则函数
        $dispatcher->setRuleFunction(function (CQMetaEvent $event) use ($context) {
            return compare_object_and_array_by_keys($event, $context->getData(), ['meta_event_type']);
        });

        $dispatcher->dispatchEvents($context->getData());
    }

    /**
     * 处理通知事件
     *
     * @param array            $data    消息数据
     * @param ContextInterface $context 上下文
     */
    private function handleNoticeEvent(array $data, ContextInterface $context): void
    {
        $dispatcher = new EventDispatcher(CQNotice::class);
        // 设定匹配规则函数
        $dispatcher->setRuleFunction(function (CQNotice $event) use ($context) {
            return compare_object_and_array_by_keys($event, $context->getData(), ['notice_type', 'sub_type', 'group_id', 'operator_id']);
        });

        $dispatcher->dispatchEvents($context->getData());
    }

    /**
     * 处理请求事件
     *
     * @param array            $data    消息数据
     * @param ContextInterface $context 上下文
     */
    private function handleRequestEvent(array $data, ContextInterface $context): void
    {
        $dispatcher = new EventDispatcher(CQRequest::class);
        // 设定匹配规则函数
        $dispatcher->setRuleFunction(function (CQRequest $event) use ($context) {
            return compare_object_and_array_by_keys($event, $context->getData(), ['request_type', 'sub_type', 'user_id', 'comment']);
        });

        $dispatcher->dispatchEvents($context->getData());
    }

    /**
     * 处理前置事件
     *
     * @param array  $data 消息数据
     * @param string $time 执行时机
     */
    private function handleBeforeEvent(array $data, string $time): EventDispatcher
    {
        $dispatcher = new EventDispatcher(CQBefore::class);
        // 设定匹配规则函数
        $dispatcher->setRuleFunction(function (CQBefore $event) use ($data, $time) {
            if ($time === 'pre') {
                $level = $event->level >= 200;
            } else {
                $level = $event->level < 200;
            }
            return $level && ($event->cq_event === $data['post_type']);
        });
        // 设定返回值处理函数
        $dispatcher->setReturnFunction(function ($result) {
            if (!$result) {
                EventDispatcher::interrupt('block');
            }
        });

        $dispatcher->dispatchEvents($data);
        return $dispatcher;
    }

    /**
     * 处理后置事件
     *
     * @param array $data 消息数据
     */
    private function handleAfterEvent(array $data): EventDispatcher
    {
        $dispatcher = new EventDispatcher(CQAfter::class);
        // 设定匹配规则函数
        $dispatcher->setRuleFunction(function (CQAfter $event) use ($data) {
            return $event->cq_event === $data['post_type'];
        });

        $dispatcher->dispatchEvents($data);
        return $dispatcher;
    }
}
