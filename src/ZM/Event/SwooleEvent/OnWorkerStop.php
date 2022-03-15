<?php

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Store\LightCache;

/**
 * Class OnWorkerStop
 * @SwooleHandler("WorkerStop")
 */
class OnWorkerStop implements SwooleEvent
{
    public function onCall(Server $server, $worker_id)
    {
        if ($worker_id == (ZMConfig::get('worker_cache')['worker'] ?? 0)) {
            LightCache::savePersistence();
        }
        Console::verbose(($server->taskworker ? 'Task' : '') . "Worker #{$worker_id} 已停止 (Worker 状态码: " . $server->getWorkerStatus($worker_id) . ')');
        Framework::removeProcessState($server->taskworker ? ZM_PROCESS_TASKWORKER : ZM_PROCESS_WORKER, $worker_id);
    }
}
