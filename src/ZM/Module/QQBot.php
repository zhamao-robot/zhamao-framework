<?php


namespace ZM\Module;

use Swoole\Coroutine;
use ZM\Annotation\CQ\CQAPIResponse;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQMessage;
use ZM\Annotation\CQ\CQMetaEvent;
use ZM\Annotation\CQ\CQNotice;
use ZM\Annotation\CQ\CQRequest;
use ZM\Event\EventDispatcher;
use ZM\Exception\InterruptException;
use ZM\Exception\WaitTimeoutException;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Utils\CoMessage;

/**
 * Class QQBot
 * @package ZM\Module
 * @ExternalModule("onebot")
 */
class QQBot
{
    /**
     * @throws InterruptException
     */
    public function handle() {
        try {
            $data = json_decode(context()->getFrame()->data, true);
            if (isset($data["post_type"])) {
                //echo TermColor::ITALIC.json_encode($data, 128|256).TermColor::RESET.PHP_EOL;
                set_coroutine_params(["data" => $data]);
                ctx()->setCache("level", 0);
                //Console::debug("Calling CQ Event from fd=" . ctx()->getConnection()->getFd());
                $this->dispatchBeforeEvents($data); // >= 200 的level before在这里执行
                if (CoMessage::resumeByWS()) {
                    EventDispatcher::interrupt();
                }
                //Console::warning("最上数据包：".json_encode($data));
                $this->dispatchEvents($data);
            } else {
                $this->dispatchAPIResponse($data);
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (WaitTimeoutException $e) {
            $e->module->finalReply($e->getMessage());
        }
    }

    public function dispatchBeforeEvents($data) {
        $before = new EventDispatcher(CQBefore::class);
        $before->setRuleFunction(function ($v) use ($data) {
            if ($v->level < 200) EventDispatcher::interrupt();
            elseif ($v->cq_event != $data["post_type"]) return false;
            return true;
        });
        $before->setReturnFunction(function ($result) {
            if (!$result) EventDispatcher::interrupt();
        });
        $before->dispatchEvents($data);
    }

    private function dispatchEvents($data) {
        //Console::warning("最xia数据包：".json_encode($data));
        switch ($data["post_type"]) {
            case "message":
                $word = explodeMsg(str_replace("\r", "", context()->getMessage()));
                if (count(explode("\n", $word[0])) >= 2) {
                    $enter = explode("\n", context()->getMessage());
                    $first = split_explode(" ", array_shift($enter));
                    $word = array_merge($first, $enter);
                    foreach ($word as $k => $v) {
                        $word[$k] = trim($word[$k]);
                    }
                }

                //分发CQCommand事件
                $dispatcher = new EventDispatcher(CQCommand::class);
                $dispatcher->setRuleFunction(function (CQCommand $v) use ($word) {
                    if ($v->match == "" && $v->pattern == "" && $v->regex == "") return false;
                    elseif (($v->user_id == 0 || ($v->user_id != 0 && $v->user_id == ctx()->getUserId())) &&
                        ($v->group_id == 0 || ($v->group_id != 0 && $v->group_id == (ctx()->getGroupId() ?? 0))) &&
                        ($v->message_type == '' || ($v->message_type != '' && $v->message_type == ctx()->getMessageType()))
                    ) {
                        if(($word[0] != "" && $v->match == $word[0]) || in_array($word[0], $v->alias)) {
                            ctx()->setCache("match", $word);
                            return true;
                        } elseif ($v->start_with != "" && mb_strpos(ctx()->getMessage(), $v->start_with) === 0) {
                            ctx()->setCache("match", [mb_substr(ctx()->getMessage(), mb_strlen($v->start_with))]);
                            return true;
                        } elseif ($v->end_with != "" && strlen(ctx()->getMessage()) == (strripos(ctx()->getMessage(), $v->end_with) + strlen($v->end_with))) {
                            ctx()->setCache("match", [substr(ctx()->getMessage(), 0, strripos(ctx()->getMessage(), $v->end_with))]);
                            return true;
                        }elseif ($v->pattern != "") {
                            $match = matchArgs($v->pattern, ctx()->getMessage());
                            if($match !== false) {
                                ctx()->setCache("match", $match);
                                return true;
                            }
                        } elseif ($v->regex != "") {
                            if(preg_match("/" . $v->regex . "/u", ctx()->getMessage(), $word2) != 0) {
                                ctx()->setCache("match", $word2);
                                return true;
                            }
                        }
                    }
                    return false;
                });
                $dispatcher->setReturnFunction(function ($result) {
                    if (is_string($result)) ctx()->reply($result);
                    EventDispatcher::interrupt();
                });
                $r = $dispatcher->dispatchEvents();
                if ($r === null) EventDispatcher::interrupt();

                //分发CQMessage事件
                $msg_dispatcher = new EventDispatcher(CQMessage::class);
                $msg_dispatcher->setRuleFunction(function ($v) {
                    return ($v->message == '' || ($v->message != '' && $v->message == context()->getData()["message"])) &&
                        ($v->user_id == 0 || ($v->user_id != 0 && $v->user_id == context()->getData()["user_id"])) &&
                        ($v->group_id == 0 || ($v->group_id != 0 && $v->group_id == (context()->getData()["group_id"] ?? 0))) &&
                        ($v->message_type == '' || ($v->message_type != '' && $v->message_type == context()->getData()["message_type"])) &&
                        ($v->raw_message == '' || ($v->raw_message != '' && $v->raw_message == context()->getData()["raw_message"]));
                });
                $msg_dispatcher->setReturnFunction(function ($result) {
                    if (is_string($result)) ctx()->reply($result);
                });
                $msg_dispatcher->dispatchEvents(ctx()->getMessage());
                return;
            case "meta_event":
                //Console::success("当前数据包：".json_encode(ctx()->getData()));
                $dispatcher = new EventDispatcher(CQMetaEvent::class);
                $dispatcher->setRuleFunction(function (CQMetaEvent $v) {
                    return ($v->meta_event_type == '' || ($v->meta_event_type != '' && $v->meta_event_type == ctx()->getData()["meta_event_type"])) &&
                        ($v->sub_type == '' || ($v->sub_type != '' && $v->sub_type == (ctx()->getData()["sub_type"] ?? '')));
                });
                //eval(BP);
                $dispatcher->dispatchEvents(ctx()->getData());
                return;
            case "notice":
                $dispatcher = new EventDispatcher(CQNotice::class);
                $dispatcher->setRuleFunction(function (CQNotice $v) {
                    return
                        ($v->notice_type == '' || ($v->notice_type != '' && $v->notice_type == ctx()->getData()["notice_type"])) &&
                        ($v->sub_type == '' || ($v->sub_type != '' && $v->sub_type == ctx()->getData()["sub_type"])) &&
                        ($v->group_id == '' || ($v->group_id != '' && $v->group_id == ctx()->getData()["group_id"])) &&
                        ($v->operator_id == '' || ($v->operator_id != '' && $v->operator_id == ctx()->getData()["operator_id"]));
                });
                $dispatcher->dispatchEvents(ctx()->getData());
                return;
            case "request":
                $dispatcher = new EventDispatcher(CQRequest::class);
                $dispatcher->setRuleFunction(function (CQRequest $v) {
                    return ($v->request_type == '' || ($v->request_type != '' && $v->request_type == ctx()->getData()['request_type'])) &&
                        ($v->sub_type == '' || ($v->sub_type != '' && $v->sub_type == ctx()->getData()['sub_type'])) &&
                        ($v->user_id == 0 || ($v->user_id != 0 && $v->user_id == ctx()->getData()["user_id"])) &&
                        ($v->comment == '' || ($v->comment != '' && $v->comment == ctx()->getData()['comment']));
                });
                $dispatcher->dispatchEvents(ctx()->getData());
                return;
        }
    }

    private function dispatchAPIResponse($req) {
        $status = $req["status"];
        $retcode = $req["retcode"];
        $data = $req["data"];
        if (isset($req["echo"]) && is_numeric($req["echo"])) {
            $r = LightCacheInside::get("wait_api", "wait_api");
            if (isset($r[$req["echo"]])) {
                $origin = $r[$req["echo"]];
                $self_id = $origin["self_id"];
                $response = [
                    "status" => $status,
                    "retcode" => $retcode,
                    "data" => $data,
                    "self_id" => $self_id,
                    "echo" => $req["echo"]
                ];
                set_coroutine_params(["cq_response" => $response]);
                $dispatcher = new EventDispatcher(CQAPIResponse::class);
                $dispatcher->setRuleFunction(function (CQAPIResponse $response) {
                    return $response->retcode == ctx()->getCQResponse()["retcode"];
                });
                $dispatcher->dispatchEvents($response);

                $origin_ctx = ctx()->copy();
                set_coroutine_params($origin_ctx);
                if (($origin["coroutine"] ?? false) !== false) {
                    SpinLock::lock("wait_api");
                    $r = LightCacheInside::get("wait_api", "wait_api");
                    $r[$req["echo"]]["result"] = $response;
                    LightCacheInside::set("wait_api", "wait_api", $r);
                    SpinLock::unlock("wait_api");
                    Coroutine::resume($origin['coroutine']);
                }
                SpinLock::lock("wait_api");
                $r = LightCacheInside::get("wait_api", "wait_api");
                unset($r[$req["echo"]]);
                LightCacheInside::set("wait_api", "wait_api", $r);
                SpinLock::unlock("wait_api");
            }
        }
    }
}
