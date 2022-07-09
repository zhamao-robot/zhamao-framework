<?php

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Event\SwooleEvent;
use ZM\Utils\DataProvider;
use ZM\Utils\Manager\ProcessManager;

/**
 * Class OnShutdown
 * @SwooleHandler("shutdown")
 */
class OnShutdown implements SwooleEvent
{
    public function onCall(Server $server)
    {
        logger()->debug('正在关闭 Master 进程，pid=' . posix_getpid());
        ProcessManager::removeProcessState(ZM_PROCESS_MASTER);
        if (DataProvider::scanDirFiles(_zm_pid_dir()) == []) {
            rmdir(_zm_pid_dir());
        }
    }
}
