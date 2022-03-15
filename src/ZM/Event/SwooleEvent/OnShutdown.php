<?php

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Utils\DataProvider;

/**
 * Class OnShutdown
 * @SwooleHandler("shutdown")
 */
class OnShutdown implements SwooleEvent
{
    public function onCall(Server $server)
    {
        Console::verbose('正在关闭 Master 进程，pid=' . posix_getpid());
        Framework::removeProcessState(ZM_PROCESS_MASTER);
        if (DataProvider::scanDirFiles(_zm_pid_dir()) == []) {
            rmdir(_zm_pid_dir());
        }
    }
}
