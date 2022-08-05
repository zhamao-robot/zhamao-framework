<?php

declare(strict_types=1);

namespace ZM\Module;

use Exception;
use Iterator;
use ZM\Annotation\CQ\CQAfter;
use ZM\Annotation\CQ\CQAPIResponse;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQMessage;
use ZM\Annotation\CQ\CQMetaEvent;
use ZM\Annotation\CQ\CQNotice;
use ZM\Annotation\CQ\CQRequest;
use ZM\Config\ZMConfig;
use ZM\Event\EventDispatcher;
use ZM\Exception\InterruptException;
use ZM\Exception\WaitTimeoutException;
use ZM\Exception\ZMKnownException;
use ZM\Utils\CoMessage;
use ZM\Utils\MessageUtil;
use ZM\Utils\SingletonTrait;

/**
 * Class QQBot
 */
class QQBot
{
    use SingletonTrait;

    /**
     * @throws ZMKnownException
     * @throws InterruptException
     */
    public function handleByEvent()
    {
        $data = json_decode(ctx()->getFrame()->data, true);
        $this->handle($data);
    }

    /**
     * @param  array|Iterator     $data  数据包
     * @param  int                $level 递归等级
     * @throws InterruptException
     * @throws ZMKnownException
     * @throws Exception
     */
    public function handle($data, int $level = 0)
    {
        try {
            if ($level > 10) {
                return;
            }
            set_coroutine_params(['data' => $data]);
            if (isset($data['post_type'])) {
                // echo TermColor::ITALIC.json_encode($data, 128|256).TermColor::RESET.PHP_EOL;
                ctx()->setCache('level', $level);
                // Console::debug("Calling CQ Event from fd=" . ctx()->getConnection()->getFd());
                if ($data['post_type'] != 'meta_event') {
                    $r = $this->dispatchBeforeEvents($data, 'pre'); // before在这里执行，元事件不执行before为减少不必要的调试日志
                    if ($r->store === 'block') {
                        EventDispatcher::interrupt();
                    }
                }
                // Console::warning("最上数据包：".json_encode($data));
            }
            if (isset($data['echo']) || isset($data['post_type'])) {
                if (CoMessage::resumeByWS()) {
                    EventDispatcher::interrupt();
                }
            }
            if (($data['post_type'] ?? 'meta_event') != 'meta_event') {
                $r = $this->dispatchBeforeEvents($data, 'post'); // before在这里执行，元事件不执行before为减少不必要的调试日志
                if ($r->store === 'block') {
                    EventDispatcher::interrupt();
                }
            }
            if (isset($data['post_type'])) {
                $this->dispatchEvents($data);
            } else {
                $this->dispatchAPIResponse($data);
            }

            if (($data['post_type'] ?? 'meta_event') != 'meta_event') {
                $r = $this->dispatchAfterEvents($data); // before在这里执行，元事件不执行before为减少不必要的调试日志
                if ($r->store === 'block') {
                    EventDispatcher::interrupt();
                }
            }
        } /* @noinspection PhpRedundantCatchClauseInspection */ catch (WaitTimeoutException $e) {
            if (($data['post_type'] ?? 'meta_event') != 'meta_event') {
                $r = $this->dispatchAfterEvents($data); // before在这里执行，元事件不执行before为减少不必要的调试日志
                if ($r->store === 'block') {
                    EventDispatcher::interrupt();
                }
            }
            $e->module->finalReply($e->getMessage());
        } catch (InterruptException $e) {
            if (($data['post_type'] ?? 'meta_event') != 'meta_event') {
                $r = $this->dispatchAfterEvents($data); // before在这里执行，元事件不执行before为减少不必要的调试日志
                if ($r->store === 'block') {
                    EventDispatcher::interrupt();
                }
            }
            throw $e;
        }
        // 这里修复CQAfter不能使用的问题，我竟然一直没写，绝了
    }

    /**
     * @param  array|Iterator $data 数据包
     * @param  string         $time 类型（pre或post）
     * @throws Exception
     */
    public function dispatchBeforeEvents($data, string $time): EventDispatcher
    {
        $before = new EventDispatcher(CQBefore::class);
        if ($time === 'pre') {
            $before->setRuleFunction(function ($v) use ($data) {
                return $v->level >= 200 && $v->cq_event == $data['post_type'];
            });
        } else {
            $before->setRuleFunction(function ($v) use ($data) {
                return $v->level < 200 && $v->cq_event == $data['post_type'];
            });
        }
        $before->setReturnFunction(function ($result) {
            if (!$result) {
                EventDispatcher::interrupt('block');
            }
        });
        $before->dispatchEvents($data);
        return $before;
    }

    /**
     * @param  array|Iterator     $data 数据包
     * @throws InterruptException
     * @throws Exception
     */
    private function dispatchEvents($data)
    {
        // Console::warning("最xia数据包：".json_encode($data));
        switch ($data['post_type']) {
            case 'message':
                // 分发CQCommand事件
                $dispatcher = new EventDispatcher(CQCommand::class);
                $dispatcher->setReturnFunction(function ($result) {
                    if (is_string($result)) {
                        ctx()->reply($result);
                    }
                    if (ctx()->getCache('has_reply') === true) {
                        EventDispatcher::interrupt();
                    }
                });
                $msg = $data['message'];
                if (is_array($msg)) {
                    $msg = MessageUtil::arrayToStr($msg);
                }
                $s = MessageUtil::matchCommand($msg, ctx()->getData());
                if ($s->status !== false) {
                    $match = $s->match;

                    $input_arguments = MessageUtil::checkArguments($s->object->class, $s->object->method, $match);
                    if (!empty($match)) {
                        ctx()->setCache('match', $match);
                    }
                    $dispatcher->dispatchEvent($s->object, null, ...$input_arguments);
                    if (is_string($dispatcher->store)) {
                        ctx()->reply($dispatcher->store);
                    }
                    if (ctx()->getCache('has_reply') === true) {
                        $policy = ZMConfig::get('global', 'onebot')['message_command_policy'] ?? 'interrupt';
                        switch ($policy) {
                            case 'interrupt':
                                EventDispatcher::interrupt();
                                // no break
                            case 'continue':
                                break;
                            default:
                                throw new Exception('未知的消息命令策略：' . $policy);
                        }
                    }
                }

                // 分发CQMessage事件
                $msg_dispatcher = new EventDispatcher(CQMessage::class);
                $msg_dispatcher->setRuleFunction(function ($v) {
                    return ($v->message == '' || ($v->message == ctx()->getMessage()))
                        && (empty($v->user_id) || ($v->user_id == ctx()->getUserId()))
                        && (empty($v->group_id) || ($v->group_id == (ctx()->getGroupId() ?? 0)))
                        && ($v->message_type == '' || ($v->message_type == ctx()->getMessageType()))
                        && ($v->raw_message == '' || ($v->raw_message == context()->getData()['raw_message']));
                });
                $msg_dispatcher->setReturnFunction(function ($result) {
                    if (is_string($result)) {
                        ctx()->reply($result);
                    }
                });
                $msg_dispatcher->dispatchEvents(ctx()->getMessage());
                return;
            case 'meta_event':
                // Console::success("当前数据包：".json_encode(ctx()->getData()));
                $dispatcher = new EventDispatcher(CQMetaEvent::class);
                $dispatcher->setRuleFunction(function (CQMetaEvent $v) {
                    return $v->meta_event_type == '' || ($v->meta_event_type == ctx()->getData()['meta_event_type']);
                });
                // eval(BP);
                $dispatcher->dispatchEvents(ctx()->getData());
                return;
            case 'notice':
                $dispatcher = new EventDispatcher(CQNotice::class);
                $dispatcher->setRuleFunction(function (CQNotice $v) {
                    return
                        ($v->notice_type == '' || ($v->notice_type == ctx()->getData()['notice_type']))
                        && ($v->sub_type == '' || ($v->sub_type == ctx()->getData()['sub_type']))
                        && (empty($v->group_id) || ($v->group_id == ctx()->getData()['group_id']))
                        && (empty($v->group_id) || ($v->operator_id == ctx()->getData()['operator_id']));
                });
                $dispatcher->dispatchEvents(ctx()->getData());
                return;
            case 'request':
                $dispatcher = new EventDispatcher(CQRequest::class);
                $dispatcher->setRuleFunction(function (CQRequest $v) {
                    return ($v->request_type == '' || ($v->request_type == ctx()->getData()['request_type']))
                        && ($v->sub_type == '' || ($v->sub_type == ctx()->getData()['sub_type']))
                        && (empty($v->user_id) || ($v->user_id == ctx()->getData()['user_id']))
                        && ($v->comment == '' || ($v->comment == ctx()->getData()['comment']));
                });
                $dispatcher->dispatchEvents(ctx()->getData());
                return;
        }
    }

    /**
     * @param  mixed     $data 分发事件数据包
     * @throws Exception
     */
    private function dispatchAfterEvents($data): EventDispatcher
    {
        $after = new EventDispatcher(CQAfter::class);
        $after->setRuleFunction(function ($v) use ($data) {
            return $v->cq_event == $data['post_type'];
        });
        $after->dispatchEvents($data);
        return $after;
    }

    /**
     * @param  mixed     $req
     * @throws Exception
     */
    private function dispatchAPIResponse($req)
    {
        set_coroutine_params(['cq_response' => $req]);
        $dispatcher = new EventDispatcher(CQAPIResponse::class);
        $dispatcher->setRuleFunction(function (CQAPIResponse $response) {
            return $response->retcode == ctx()->getCQResponse()['retcode'];
        });
        $dispatcher->dispatchEvents($req);
    }
}
