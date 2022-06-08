<?php

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Event\SwooleEvent;
use ZM\Store\LightCache;
use ZM\Utils\Manager\ProcessManager;

/**
 * Class OnWorkerStop
 * @SwooleHandler("WorkerStop")
 */
class OnWorkerStop implements SwooleEvent
{
    public function onCall(Server $server, $worker_id)
    {
        if ($worker_id == (ZMConfig::get('global.worker_cache.worker') ?? 0)) {
            LightCache::savePersistence();
        }
        logger()->debug('{worker}#{worker_id} 已停止（状态码：{status}）', ['worker' => ($server->taskworker ? 'Task' : '') . 'Worker', 'worker_id' => $worker_id, 'status' => $server->getWorkerStatus($worker_id)]);
        ProcessManager::removeProcessState($server->taskworker ? ZM_PROCESS_TASKWORKER : ZM_PROCESS_WORKER, $worker_id);
    }
}
