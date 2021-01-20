<?php


namespace ZM\Utils;


use Co;
use Exception;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;

class CoMessage
{
    /**
     * @param array $hang
     * @param array $compare
     * @param int $timeout
     * @return bool
     * @throws Exception
     */
    public static function yieldByWS(array $hang, array $compare, $timeout = 600) {
        $cid = Co::getuid();
        $api_id = ZMAtomic::get("wait_msg_id")->add(1);
        $hang["compare"] = $compare;
        $hang["coroutine"] = $cid;
        $hang["worker_id"] = server()->worker_id;
        $hang["result"] = null;
        SpinLock::lock("wait_api");
        $wait = LightCacheInside::get("wait_api", "wait_api");
        $wait[$api_id] = $hang;
        LightCacheInside::set("wait_api", "wait_api", $wait);
        SpinLock::unlock("wait_api");
        $id = swoole_timer_after($timeout * 1000, function () use ($api_id) {
            $r = LightCacheInside::get("wait_api", "wait_api")[$api_id] ?? null;
            if (is_array($r)) {
                Co::resume($r["coroutine"]);
            }
        });
        Co::suspend();
        SpinLock::lock("wait_api");
        $sess = LightCacheInside::get("wait_api", "wait_api");
        $result = $sess[$api_id]["result"];
        unset($sess[$api_id]);
        LightCacheInside::set("wait_api", "wait_api", $sess);
        SpinLock::unlock("wait_api");
        if (isset($id)) swoole_timer_clear($id);
        if ($result === null) return false;
        return $result;
    }
}
