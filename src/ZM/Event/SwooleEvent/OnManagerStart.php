<?php /** @noinspection PhpUnusedParameterInspection */

/** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Event\SwooleEvent;


use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Utils\SignalListener;

/**
 * Class OnManagerStart
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("ManagerStart")
 */
class OnManagerStart implements SwooleEvent
{
    public function onCall(Server $server) {
        if (!Framework::$argv["disable-safe-exit"]) {
            SignalListener::signalManager();
        }
        Console::verbose("进程 Manager 已启动");
    }
}