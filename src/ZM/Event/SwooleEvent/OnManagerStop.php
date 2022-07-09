<?php

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Process;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Event\SwooleEvent;
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
        logger()->debug('进程 Manager 已停止！');
        ProcessManager::removeProcessState(ZM_PROCESS_MANAGER);
    }
}
