<?php


namespace ZM\Event\SwooleEvent;


use Swoole\Process;
use Swoole\WebSocket\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;

/**
 * Class OnBeforeReload
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("BeforeReload")
 */
class OnBeforeReload implements SwooleEvent
{
    public function onCall(Server $server) {
        Console::info(Console::setColor("Reloading server...", "gold"));
        for ($i = 0; $i < ZM_WORKER_NUM; ++$i) {
            Process::kill(zm_atomic("_#worker_" . $i)->get(), SIGUSR1);
        }

        usleep(800 * 1000);
    }
}