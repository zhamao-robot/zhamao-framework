<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Driver\Process\ProcessManager;
use OneBot\Util\Singleton;
use ZM\Framework;
use ZM\Process\ProcessStateManager;

class WorkerEventListener
{
    use Singleton;

    public function onWorkerStart()
    {
        // 自注册一下，刷新当前进程的logger进程banner
        ob_logger_register(ob_logger());
        // 如果没有引入参数disable-safe-exit，则监听 Ctrl+C
        if (!Framework::getInstance()->getArgv()['disable-safe-exit'] && PHP_OS_FAMILY !== 'Windows') {
            SignalListener::getInstance()->signalWorker();
        }
        logger()->debug('Worker #' . ProcessManager::getProcessId() . ' started');

        if (($name = Framework::getInstance()->getDriver()->getName()) === 'swoole') {
            /* @phpstan-ignore-next-line */
            $server = Framework::getInstance()->getDriver()->getSwooleServer();
            ProcessStateManager::saveProcessState(ZM_PROCESS_WORKER, $server->worker_pid, ['worker_id' => $server->worker_id]);
        } elseif ($name === 'workerman' && DIRECTORY_SEPARATOR !== '\\' && extension_loaded('posix')) {
            ProcessStateManager::saveProcessState(ZM_PROCESS_WORKER, posix_getpid(), ['worker_id' => ProcessManager::getProcessId()]);
        }
    }

    public function onWorkerStop()
    {
        logger()->debug('Worker #' . ProcessManager::getProcessId() . ' stopping');
        ProcessStateManager::removeProcessState(ZM_PROCESS_WORKER, ProcessManager::getProcessId());
    }
}
