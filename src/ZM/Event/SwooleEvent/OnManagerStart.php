<?php /** @noinspection PhpUnusedParameterInspection */

/** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Event\SwooleEvent;


use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;

/**
 * Class OnManagerStart
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("ManagerStart")
 */
class OnManagerStart implements SwooleEvent
{
    public function onCall(Server $server) {
        pcntl_signal(SIGINT, function () {
            Console::verbose("Interrupted in manager!");
        });
        Console::verbose("进程 Manager 已启动");
    }
}