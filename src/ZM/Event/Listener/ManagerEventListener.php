<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Util\Singleton;
use ZM\Process\ProcessStateManager;

class ManagerEventListener
{
    use Singleton;

    public function onManagerStart()
    {
        // 自注册一下，刷新当前进程的logger进程banner
        ob_logger_register(ob_logger());
        logger()->debug('Manager process started');

        SignalListener::getInstance()->signalManager();

        /* @noinspection PhpComposerExtensionStubsInspection */
        ProcessStateManager::saveProcessState(ZM_PROCESS_MANAGER, posix_getpid());
    }

    public function onManagerStop()
    {
    }
}
