<?php


namespace ZM\Event\SwooleEvent;


use Error;
use Exception;
use Swoole\Coroutine;
use Swoole\WebSocket\Frame;
use ZM\Annotation\Swoole\OnMessageEvent;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Console\TermColor;
use ZM\Context\Context;
use ZM\Event\EventDispatcher;
use ZM\Event\SwooleEvent;

/**
 * Class OnMessage
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("message")
 */
class OnMessage implements SwooleEvent
{
    /**
     * @noinspection PhpUnreachableStatementInspection
     */
    public function onCall($server, Frame $frame) {
        Console::debug("Calling Swoole \"message\" from fd=" . $frame->fd . ": " . TermColor::ITALIC . $frame->data . TermColor::RESET);
        unset(Context::$context[Coroutine::getCid()]);
        $conn = ManagerGM::get($frame->fd);
        set_coroutine_params(["server" => $server, "frame" => $frame, "connection" => $conn]);
        $dispatcher1 = new EventDispatcher(OnMessageEvent::class);
        $dispatcher1->setRuleFunction(function ($v) {
            if ($v->getRule() == '') return true;
            else return eval("return " . $v->getRule() . ";");
        });


        $dispatcher = new EventDispatcher(OnSwooleEvent::class);
        $dispatcher->setRuleFunction(function ($v) {
            if ($v->getRule() == '') {
                return strtolower($v->type) == 'message';
            } else {
                /** @noinspection PhpUnreachableStatementInspection
                 * @noinspection RedundantSuppression
                 */
                if (strtolower($v->type) == 'message' && eval("return " . $v->getRule() . ";")) return true;
                else return false;
            }
        });
        try {
            //$starttime = microtime(true);
            $dispatcher1->dispatchEvents($conn);
            $dispatcher->dispatchEvents($conn);
            //Console::success("Used ".round((microtime(true) - $starttime) * 1000, 3)." ms!");
        } catch (Exception $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error(zm_internal_errcode("E00017") . "Uncaught exception " . get_class($e) . " when calling \"message\": " . $error_msg);
            Console::trace();
        } catch (Error $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error(zm_internal_errcode("E00017") . "Uncaught " . get_class($e) . " when calling \"message\": " . $error_msg);
            Console::trace();
        }

    }
}