<?php


namespace ZM\Utils;


use Co;
use Exception;
use Swoole\Event;
use Swoole\Timer;
use ZM\Console\Console;
use ZM\Store\LightCache;
use ZM\Store\LightCacheInside;
use ZM\Store\ZMAtomic;
use ZM\Store\ZMBuf;

class ZMUtil
{
    public static function stop() {
        Console::warning(Console::setColor("Stopping server...", "red"));
        LightCache::savePersistence();
        if (ZMBuf::$terminal !== null)
            Event::del(ZMBuf::$terminal);
        ZMAtomic::get("stop_signal")->set(1);
        try {
            LightCache::set('stop', 'OK');
        } catch (Exception $e) {
        }
        server()->shutdown();
        server()->stop();
    }

    public static function reload($delay = 800) {
        Console::info(Console::setColor("Reloading server...", "gold"));
        usleep($delay * 1000);
        foreach ((LightCacheInside::get("wait_api", "wait_api") ?? []) as $k => $v) {
            if (($v["result"] ?? false) === null && isset($v["coroutine"])) Co::resume($v["coroutine"]);
        }
        LightCacheInside::unset("wait_api", "wait_api");
        foreach (server()->connections as $v) {
            server()->close($v);
        }
        LightCache::savePersistence();
        //DataProvider::saveBuffer();
        Timer::clearAll();
        server()->reload();
    }

    public static function getModInstance($class) {
        if (!isset(ZMBuf::$instance[$class])) {
            //Console::debug("Class instance $class not exist, so I created it.");
            return ZMBuf::$instance[$class] = new $class();
        } else {
            return ZMBuf::$instance[$class];
        }
    }

    public static function sendActionToWorker($target_id, $action, $data) {
        server()->sendMessage(json_encode(["action" => $action, "data" => $data]), $target_id);
    }
}
