<?php


namespace ZM\Event\SwooleEvent;


use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Store\LightCache;

/**
 * Class OnWorkerStop
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("WorkerStop")
 */
class OnWorkerStop implements SwooleEvent
{
    public function onCall(Server $server, $worker_id) {
        if ($worker_id == (ZMConfig::get("worker_cache")["worker"] ?? 0)) {
            LightCache::savePersistence();
        }
        Console::verbose(($server->taskworker ? "Task" : "") . "Worker #$worker_id 已停止");
    }
}