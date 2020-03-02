<?php


namespace ZM\Event\CQ;


use Co;
use Framework\ZMBuf;
use ZM\Annotation\CQ\CQAfter;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQMessage;
use ZM\Connection\ConnectionManager;
use ZM\Exception\WaitTimeoutException;
use ZM\ModBase;
use ZM\ModHandleType;

class MessageEvent
{
    private $function_call = false;
    private $data;
    private $circle;
    /**
     * @var \ZM\Event\Swoole\MessageEvent
     */
    private $swoole_event;

    public function __construct($data, \ZM\Event\Swoole\MessageEvent $event, $circle = 0) {
        $this->data = $data;
        $this->swoole_event = $event;
        $this->circle = $circle;
    }

    public function onBefore() {
        foreach (ZMBuf::$events[CQBefore::class][CQMessage::class] ?? [] as $v) {
            $c = $v->class;
            $class = new $c([
                "data" => $this->data,
                "frame" => $this->swoole_event->frame,
                "server" => $this->swoole_event->server,
                "connection" => ConnectionManager::get($this->swoole_event->frame->fd)
            ], ModHandleType::CQ_MESSAGE);
            $r = call_user_func_array([$class, $v->method], []);
            if (!$r || $class->block_continue) return false;
        }
        foreach (ZMBuf::get("wait_api", []) as $k => $v) {
            if($this->data["user_id"] == $v["user_id"] &&
                $this->data["self_id"] == $v["self_id"] &&
                $this->data["message_type"] == $v["message_type"] &&
                ($this->data[$this->data["message_type"]."_id"] ?? $this->data["user_id"]) ==
                ($v[$v["message_type"]."_id"] ?? $v["user_id"])){
                $v["result"] = $this->data["message"];
                ZMBuf::appendKey("wait_api", $k, $v);
                Co::resume($v["coroutine"]);
                return false;
            }
        }
        return true;
    }

    /** @noinspection PhpRedundantCatchClauseInspection */
    public function onActivate() {
        try {
            $word = split_explode(" ", str_replace("\r", "", $this->data["message"]));
            if (count(explode("\n", $word[0])) >= 2) {
                $enter = explode("\n", $this->data["message"]);
                $first = split_explode(" ", array_shift($enter));
                $word = array_merge($first, $enter);
                foreach ($word as $k => $v) {
                    $word[$k] = trim($word[$k]);
                }
            }
            /** @var ModBase[] $obj */
            $obj = [];
            foreach (ZMBuf::$events[CQCommand::class] ?? [] as $v) {
                /** @var CQCommand $v */
                if ($v->match == "" && $v->regexMatch == "") continue;
                else {
                    $c = $v->class;
                    if (!isset($obj[$c]))
                        $obj[$c] = new $c([
                            "data" => $this->data,
                            "frame" => $this->swoole_event->frame,
                            "server" => $this->swoole_event->server,
                            "connection" => ConnectionManager::get($this->swoole_event->frame->fd)
                        ], ModHandleType::CQ_MESSAGE);
                    if ($word[0] != "" && $v->match == $word[0]) {
                        $r = call_user_func([$obj[$c], $v->method], $word);
                        if (is_string($r)) $obj[$c]->reply($r);
                        $this->function_call = true;
                        return;
                    } elseif (($args = matchArgs($v->regexMatch, $this->data["message"])) !== false) {
                        $r = call_user_func([$obj[$c], $v->method], $args);
                        if (is_string($r)) $obj[$c]->reply($r);
                        $this->function_call = true;
                        return;
                    }
                }
            }
            foreach (ZMBuf::$events[CQMessage::class] ?? [] as $v) {
                /** @var CQMessage $v */
                if (
                    ($v->message == '' || ($v->message != '' && $v->message == $this->data["message"])) &&
                    ($v->user_id == 0 || ($v->user_id != 0 && $v->user_id == $this->data["user_id"])) &&
                    ($v->group_id == 0 || ($v->group_id != 0 && $v->group_id == ($this->data["group_id"] ?? 0))) &&
                    ($v->discuss_id == 0 || ($v->discuss_id != 0 && $v->discuss_id == ($this->data["discuss_id"] ?? 0))) &&
                    ($v->message_type == '' || ($v->message_type != '' && $v->message_type == $this->data["message_type"])) &&
                    ($v->raw_message == '' || ($v->raw_message != '' && $v->raw_message == $this->data["raw_message"]))) {
                    $c = $v->class;
                    if (!isset($obj[$c]))
                        $obj[$c] = new $c([
                            "data" => $this->data,
                            "frame" => $this->swoole_event->frame,
                            "server" => $this->swoole_event->server,
                            "connection" => ConnectionManager::get($this->swoole_event->frame->fd)
                        ], ModHandleType::CQ_MESSAGE);
                    $r = call_user_func([$obj[$c], $v->method], $this->data["message"]);
                    if (is_string($r)) $obj[$c]->reply($r);
                    if ($obj[$c]->block_continue) return;
                }
            }
        } catch (WaitTimeoutException $e) {

            $e->module->finalReply($e->getMessage());
        }
    }

    /**
     * 在调用完事件后执行的
     */
    public function onAfter() {
        foreach (ZMBuf::$events[CQAfter::class][CQMessage::class] ?? [] as $v) {
            $c = $v->class;
            $class = new $c([
                "data" => $this->data,
                "frame" => $this->swoole_event->frame,
                "server" => $this->swoole_event->server,
                "connection" => ConnectionManager::get($this->swoole_event->frame->fd)
            ], ModHandleType::CQ_MESSAGE);
            $r = call_user_func_array([$class, $v->method], []);
            if (!$r || $class->block_continue) return false;
        }
        return true;
    }

    public function hasReply() {
        return $this->function_call;
    }
}