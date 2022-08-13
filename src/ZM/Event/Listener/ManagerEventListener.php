<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Util\Singleton;
use ZM\Exception\ZMKnownException;
use ZM\Process\ProcessStateManager;

class ManagerEventListener
{
    use Singleton;

    /**
     * Manager 进程启动的回调（仅 Swoole 驱动才会回调）
     */
    public function onManagerStart()
    {
        // 自注册一下，刷新当前进程的logger进程banner
        ob_logger_register(ob_logger());
        logger()->debug('Manager process started');

        // 注册 Manager 进程的信号
        SignalListener::getInstance()->signalManager();

        /* @noinspection PhpComposerExtensionStubsInspection */
        ProcessStateManager::saveProcessState(ZM_PROCESS_MANAGER, posix_getpid());
    }

    /**
     * Manager 进程停止的回调（仅 Swoole 驱动才会回调）
     * @throws ZMKnownException
     */
    public function onManagerStop()
    {
        logger()->debug('Manager process stopped');
        ProcessStateManager::removeProcessState(ZM_PROCESS_MANAGER);
    }
}
