<?php


namespace ZM\Event\CQ;


use Co;
use Doctrine\Common\Annotations\AnnotationException;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Event\EventManager;
use ZM\Store\ZMBuf;
use ZM\Annotation\CQ\CQAfter;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQMessage;
use ZM\Event\EventHandler;
use ZM\Exception\WaitTimeoutException;
use ZM\Http\Response;

class MessageEvent
{
    private $function_call = false;
    private $data;
    private $circle;
    /** @var ConnectionObject|Response */
    private $connection;

    public function __construct($data, $conn_or_response, $circle = 0) {
        $this->data = $data;
        $this->connection = $conn_or_response;
        $this->circle = $circle;
    }

    /**
     * @return bool
     * @throws AnnotationException
     */
    public function onBefore() {
        $dispatcher = new EventDispatcher(CQBefore::class . "::message");
        $dispatcher->setRuleFunction(function ($v) {
            if($v->level < 200) EventDispatcher::interrupt();
            return true;
        });
        $dispatcher->setReturnFunction(function($result){
            if(!$result) EventDispatcher::interrupt();
        });
        $dispatcher->dispatchEvents();

        foreach (ZMBuf::get("wait_api", []) as $k => $v) {
            if(zm_data_hash(ctx()->getData()) == $v["hash"]) {
                $v["result"] = context()->getData()["message"];
                ZMBuf::appendKey("wait_api", $k, $v);
                Co::resume($v["coroutine"]);
                return false;
            }
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
        foreach (EventManager::$events[CQBefore::class]["message"] ?? [] as $v) {
            if ($v->level >= 200) continue;
            $c = $v->class;
            if (ctx()->getCache("level") != 0) continue;
            EventHandler::callWithMiddleware(
                $c,
                $v->method,
                ["data" => context()->getData(), "connection" => $this->connection],
                [],
                function ($r) {
                    if (!$r) context()->setCache("block_continue", true);
                }
            );
            if (context()->getCache("block_continue") === true) return false;
        }
        return true;
    }

    /**
     * @throws AnnotationException
     * @noinspection PhpRedundantCatchClauseInspection
     */
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

            //分发CQCommand事件
            $dispatcher = new EventDispatcher(CQCommand::class);
            $dispatcher->setRuleFunction(function ($v) use ($word) {
                if ($v->match == "" && $v->regexMatch == "" && $v->fullMatch == "") return false;
                elseif (($v->user_id == 0 || ($v->user_id != 0 && $v->user_id == ctx()->getUserId())) &&
                    ($v->group_id == 0 || ($v->group_id != 0 && $v->group_id == (ctx()->getGroupId() ?? 0))) &&
                    ($v->message_type == '' || ($v->message_type != '' && $v->message_type == ctx()->getMessageType()))
                ) {
                    if (($word[0] != "" && $v->match == $word[0]) ||
                        in_array($word[0], $v->alias) ||
                        ($v->regexMatch != "" && ($args = matchArgs($v->regexMatch, ctx()->getMessage())) !== false) ||
                        ($v->fullMatch != "" && (preg_match("/" . $v->fullMatch . "/u", ctx()->getMessage(), $args)) != 0)) {
                        return true;
                    }
                }
                return false;
            });
            $dispatcher->setReturnFunction(function ($result) {
                if (is_string($result)) ctx()->reply($result);
                EventDispatcher::interrupt();
            });
            $r = $dispatcher->dispatchEvents($word);
            if ($r === false) return;

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
        } catch (WaitTimeoutException $e) {
            $e->module->finalReply($e->getMessage());
        }
    }

    /**
     * 在调用完事件后执行的
     * @throws AnnotationException
     */
    public function onAfter() {
        context()->setCache("block_continue", null);
        foreach (ZMBuf::$events[CQAfter::class]["message"] ?? [] as $v) {
            $c = $v->class;
            EventHandler::callWithMiddleware(
                $c,
                $v->method,
                ["data" => context()->getData(), "connection" => $this->connection],
                [],
                function ($r) {
                    if (!$r) context()->setCache("block_continue", true);
                }
            );
            if (context()->getCache("block_continue") === true) return false;
        }
        return true;
    }

    public function hasReply() {
        return $this->function_call;
    }
}
