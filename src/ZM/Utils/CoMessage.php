<?php

declare(strict_types=1);

namespace ZM\Utils;

use Exception;
use Swoole\Coroutine;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Utils\Manager\WorkerManager;

class CoMessage
{
    /**
     * @return mixed
     */
    public static function yieldByWS(array $hang, array $compare, int $timeout = 600)
    {
        $cid = Coroutine::getuid();
        $api_id = ZMAtomic::get('wait_msg_id')->add(1);
        $hang['compare'] = $compare;
        $hang['coroutine'] = $cid;
        $hang['worker_id'] = server()->worker_id;
        $hang['result'] = null;
        SpinLock::lock('wait_api');
        $wait = LightCacheInside::get('wait_api', 'wait_api');
        $wait[$api_id] = $hang;
        LightCacheInside::set('wait_api', 'wait_api', $wait);
        SpinLock::unlock('wait_api');
        $id = swoole_timer_after($timeout * 1000, function () use ($api_id) {
            $r = LightCacheInside::get('wait_api', 'wait_api')[$api_id] ?? null;
            if (is_array($r)) {
                Coroutine::resume($r['coroutine']);
            }
        });
        Coroutine::suspend();
        SpinLock::lock('wait_api');
        $sess = LightCacheInside::get('wait_api', 'wait_api');
        $result = $sess[$api_id]['result'] ?? null;
        unset($sess[$api_id]);
        LightCacheInside::set('wait_api', 'wait_api', $sess);
        SpinLock::unlock('wait_api');
        swoole_timer_clear($id);
        if ($result === null) {
            return false;
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    public static function resumeByWS(): bool
    {
        $dat = ctx()->getData();
        $last = null;
        SpinLock::lock('wait_api');
        $all = LightCacheInside::get('wait_api', 'wait_api') ?? [];
        foreach ($all as $k => $v) {
            if (!isset($v['compare'])) {
                continue;
            }
            foreach ($v['compare'] as $vs) {
                if (!isset($v[$vs], $dat[$vs])) {
                    continue 2;
                }
                if ($v[$vs] != $dat[$vs]) {
                    continue 2;
                }
            }
            $last = $k;
        }
        if ($last !== null) {
            $all[$last]['result'] = $dat;
            LightCacheInside::set('wait_api', 'wait_api', $all);
            SpinLock::unlock('wait_api');
            if ($all[$last]['worker_id'] != server()->worker_id) {
                WorkerManager::sendActionToWorker($all[$last]['worker_id'], 'resume_ws_message', $all[$last]);
            } else {
                Coroutine::resume($all[$last]['coroutine']);
            }
            return true;
        }
        SpinLock::unlock('wait_api');
        return false;
    }
}
