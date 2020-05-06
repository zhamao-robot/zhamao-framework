<?php


namespace ZM\Event\CQ;


use Co;
use Framework\Console;
use Framework\ZMBuf;
use ZM\Annotation\CQ\CQAfter;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQMessage;
use ZM\Connection\WSConnection;
use ZM\Exception\WaitTimeoutException;
use ZM\Http\Response;
use ZM\ModBase;
use ZM\ModHandleType;

class MessageEvent
{
    private $function_call = false;
    private $data;
    private $circle;
    /** @var WSConnection|Response */
    private $connection;

    public function __construct($data, $conn_or_response, $circle = 0) {
        $this->data = $data;
        $this->connection = $conn_or_response;
        $this->circle = $circle;
    }

    public function onBefore() {
        foreach (ZMBuf::$events[CQBefore::class]["message"] ?? [] as $v) {
            $c = $v->class;
            $class = new $c([
                "data" => context()->getData(),
                "connection" => $this->connection
            ], ModHandleType::CQ_MESSAGE);
            Console::debug("Calling CQBefore: " . $c . " -> " . $v->method);
            $r = call_user_func_array([$class, $v->method], []);
            if (!$r || $class->block_continue) return false;
        }
        foreach (ZMBuf::get("wait_api", []) as $k => $v) {
            if (context()->getData()["user_id"] == $v["user_id"] &&
                context()->getData()["self_id"] == $v["self_id"] &&
                context()->getData()["message_type"] == $v["message_type"] &&
                (context()->getData()[context()->getData()["message_type"] . "_id"] ?? context()->getData()["user_id"]) ==
                ($v[$v["message_type"] . "_id"] ?? $v["user_id"])) {
                $v["result"] = context()->getData()["message"];
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
            $word = split_explode(" ", str_replace("\r", "", context()->getMessage()));
            if (count(explode("\n", $word[0])) >= 2) {
                $enter = explode("\n", context()->getMessage());
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
                            "data" => context()->getData(),
                            "connection" => context()->getConnection()
                        ], ModHandleType::CQ_MESSAGE);
                    if ($word[0] != "" && $v->match == $word[0]) {
                        Console::debug("Calling $c -> {$v->method}");
                        $r = call_user_func([$obj[$c], $v->method], $word);
                        if (is_string($r)) $obj[$c]->reply($r);
                        $this->function_call = true;
                        return;
                    } elseif ($v->regexMatch != "" && ($args = matchArgs($v->regexMatch, context()->getMessage())) !== false) {
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
                    ($v->message == '' || ($v->message != '' && $v->message == context()->getData()["message"])) &&
                    ($v->user_id == 0 || ($v->user_id != 0 && $v->user_id == context()->getData()["user_id"])) &&
                    ($v->group_id == 0 || ($v->group_id != 0 && $v->group_id == (context()->getData()["group_id"] ?? 0))) &&
                    ($v->discuss_id == 0 || ($v->discuss_id != 0 && $v->discuss_id == (context()->getData()["discuss_id"] ?? 0))) &&
                    ($v->message_type == '' || ($v->message_type != '' && $v->message_type == context()->getData()["message_type"])) &&
                    ($v->raw_message == '' || ($v->raw_message != '' && $v->raw_message == context()->getData()["raw_message"]))) {
                    $c = $v->class;
                    if (!isset($obj[$c]))
                        $obj[$c] = new $c([
                            "data" => context()->getData(),
                            "connection" => $this->connection
                        ], ModHandleType::CQ_MESSAGE);
                    $r = call_user_func([$obj[$c], $v->method], context()->getData()["message"]);
                    if (is_string($r)) $obj[$c]->reply($r);
                    if (context()->getCache("block_continue") === true) return;
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
        foreach (ZMBuf::$events[CQAfter::class]["message"] ?? [] as $v) {
            $c = $v->class;
            $class = new $c([
                "data" => context()->getData(),
                "connection" => $this->connection
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
