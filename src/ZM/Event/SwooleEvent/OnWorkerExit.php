<?php /** @noinspection PhpUnusedParameterInspection */


namespace ZM\Event\SwooleEvent;


use Swoole\Coroutine;
use Swoole\Server;
use Swoole\Timer;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Store\LightCacheInside;

/**
 * Class OnWorkerExit
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("WorkerExit")
 */
class OnWorkerExit implements SwooleEvent
{
    public function onCall(Server $server, $worker_id) {
        Timer::clearAll();
        foreach((LightCacheInside::get("wait_api", "wait_api") ?? []) as $v) {
            if (($v["worker_id"] ?? -1) == $worker_id && isset($v["coroutine"])) {
                Coroutine::resume($v["coroutine"]);
            }
        }
        Console::info("正在结束 Worker #".$worker_id."，进程内可能有事务在运行...");
    }
}