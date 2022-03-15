<?php

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Process;
use Swoole\WebSocket\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;

/**
 * Class OnBeforeReload
 * @SwooleHandler("BeforeReload")
 */
class OnBeforeReload implements SwooleEvent
{
    public function onCall(Server $server)
    {
        Console::info(Console::setColor('Reloading server...', 'gold'));
        for ($i = 0; $i < ZM_WORKER_NUM; ++$i) {
            Process::kill(zm_atomic('_#worker_' . $i)->get(), SIGUSR1);
        }
        $conf = ZMConfig::get('global', 'runtime')['reload_delay_time'] ?? 800;
        if ($conf !== 0) {
            usleep($conf * 1000);
        }
    }
}
