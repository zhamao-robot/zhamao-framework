<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Event\SwooleEvent;


use Error;
use Exception;
use Swoole\Event;
use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Store\ZMBuf;
use ZM\Utils\DataProvider;
use ZM\Utils\SignalListener;
use ZM\Utils\Terminal;
use ZM\Utils\ZMUtil;

/**
 * Class OnStart
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("start")
 */
class OnStart implements SwooleEvent
{
    public function onCall(Server $server) {
        Console::debug("Calling onStart event(1)");
        if (!Framework::$argv["disable-safe-exit"]) {
            SignalListener::signalMaster($server);
        }
    }


}