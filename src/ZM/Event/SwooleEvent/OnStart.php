<?php

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Utils\SignalListener;

/**
 * Class OnStart
 * @SwooleHandler("start")
 */
class OnStart implements SwooleEvent
{
    public function onCall(Server $server)
    {
        Console::debug('Calling onStart event(1)');
        if (!Framework::$argv['disable-safe-exit']) {
            SignalListener::signalMaster($server);
        }
        Framework::saveProcessState(ZM_PROCESS_MASTER, $server->master_pid, [
            'stdout' => ZMConfig::get('global')['swoole']['log_file'],
            'daemon' => (bool) Framework::$argv['daemon'],
        ]);
    }
}
