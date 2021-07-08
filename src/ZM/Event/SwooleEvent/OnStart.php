<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Event\SwooleEvent;


use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Utils\DataProvider;
use ZM\Utils\SignalListener;

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
        if (Framework::$argv["daemon"]) {
            $daemon_data = json_encode([
                "pid" => $server->master_pid,
                "stdout" => ZMConfig::get("global")["swoole"]["log_file"]
            ], 128 | 256);
            file_put_contents(DataProvider::getWorkingDir() . "/.daemon_pid", $daemon_data);
        }
    }


}