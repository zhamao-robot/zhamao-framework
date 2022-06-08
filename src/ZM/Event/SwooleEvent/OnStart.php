<?php

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Utils\Manager\ProcessManager;
use ZM\Utils\SignalListener;

/**
 * Class OnStart
 * @SwooleHandler("start")
 */
class OnStart implements SwooleEvent
{
    public function onCall(Server $server)
    {
        logger()->debug('Calling onStart event(1)');
        if (!Framework::$argv['disable-safe-exit']) {
            SignalListener::signalMaster($server);
        }
        ProcessManager::saveProcessState(ZM_PROCESS_MASTER, $server->master_pid, [
            'stdout' => ZMConfig::get('global')['swoole']['log_file'],
            'daemon' => (bool) Framework::$argv['daemon'],
        ]);
    }
}
