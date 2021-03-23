<?php


namespace ZM\Utils;


use Co;
use Exception;
use Swoole\Event;
use Swoole\Timer;
use ZM\Console\Console;
use ZM\Store\LightCache;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Store\ZMBuf;

class ZMUtil
{
    /**
     * @throws Exception
     */
    public static function stop() {
        if (SpinLock::tryLock("_stop_signal") === false) return;
        Console::warning(Console::setColor("Stopping server...", "red"));
        if (Console::getLevel() >= 4) Console::trace();
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

    /**
     * @param int $delay
     * @throws Exception
     */
    public static function reload($delay = 800) {
        if (server()->worker_id !== -1) {
            Console::info(server()->worker_id);
            zm_atomic("_int_is_reload")->set(1);
            system("kill -INT " . intval(server()->master_pid));
            return;
        }
        Console::info(Console::setColor("Reloading server...", "gold"));
        usleep($delay * 1000);
        foreach ((LightCacheInside::get("wait_api", "wait_api") ?? []) as $k => $v) {
            if (($v["result"] ?? false) === null && isset($v["coroutine"])) Co::resume($v["coroutine"]);
        }
        LightCacheInside::unset("wait_api", "wait_api");
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
