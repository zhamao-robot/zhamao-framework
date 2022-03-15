<?php

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Process;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Utils\Manager\ProcessManager;

/**
 * Class OnManagerStop
 * @SwooleHandler("ManagerStop")
 */
class OnManagerStop implements SwooleEvent
{
    public function onCall()
    {
        foreach (ProcessManager::$user_process as $v) {
            if (posix_getsid($v->pid) !== false) {
                Process::kill($v->pid, SIGTERM);
            }
        }
        Console::verbose('进程 Manager 已停止！');
        Framework::removeProcessState(ZM_PROCESS_MANAGER);
    }
}
