<?php


namespace ZM\Event\SwooleEvent;


use Error;
use Exception;
use Swoole\Coroutine;
use ZM\Annotation\Swoole\OnCloseEvent;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Context\Context;
use ZM\Event\EventDispatcher;
use ZM\Event\SwooleEvent;
use ZM\Store\LightCacheInside;

/**
 * Class OnClose
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("close")
 */
class OnClose implements SwooleEvent
{
    /** @noinspection PhpUnreachableStatementInspection */
    public function onCall($server, $fd) {
        unset(Context::$context[Coroutine::getCid()]);
        Console::debug("Calling Swoole \"close\" event from fd=" . $fd);
        $conn = ManagerGM::get($fd);
        if ($conn === null) return;
        set_coroutine_params(["server" => $server, "connection" => $conn, "fd" => $fd]);

        $dispatcher1 = new EventDispatcher(OnCloseEvent::class);
        $dispatcher1->setRuleFunction(function ($v) {
            return $v->connect_type == ctx()->getConnection()->getName() && eval("return " . $v->getRule() . ";");
        });

        $dispatcher = new EventDispatcher(OnSwooleEvent::class);
        $dispatcher->setRuleFunction(function ($v) {
            if ($v->getRule() == '') {
                return strtolower($v->type) == 'close';
            } else {
                /** @noinspection PhpUnreachableStatementInspection */
                if (strtolower($v->type) == 'close' && eval("return " . $v->getRule() . ";")) return true;
                else return false;
            }
        });
        try {
            $obb_onebot = ZMConfig::get("global", "onebot") ??
                ZMConfig::get("global", "modules")["onebot"] ??
                ["status" => true, "single_bot_mode" => false, "message_level" => 99999];
            if ($conn->getName() === 'qq' && $obb_onebot["status"] === true) {
                if ($obb_onebot["single_bot_mode"]) {
                    LightCacheInside::set("connect", "conn_fd", -1);
                }
            }
            $dispatcher1->dispatchEvents($conn);
            $dispatcher->dispatchEvents($conn);
        } catch (Exception $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error(zm_internal_errcode("E00016") . "Uncaught exception " . get_class($e) . " when calling \"close\": " . $error_msg);
            Console::trace();
        } catch (Error $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error(zm_internal_errcode("E00016") . "Uncaught " . get_class($e) . " when calling \"close\": " . $error_msg);
            Console::trace();
        }
        ManagerGM::popConnect($fd);
    }
}