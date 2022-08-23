<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Driver\Workerman\Worker;
use OneBot\Util\Singleton;
use Swoole\Server;
use ZM\Exception\ZMKnownException;
use ZM\Framework;
use ZM\Process\ProcessStateManager;
use ZM\Store\FileSystem;

class MasterEventListener
{
    use Singleton;

    public function onMasterStart()
    {
        if (Framework::getInstance()->getDriver()->getName() === 'swoole') {
            /* @phpstan-ignore-next-line */
            $server = Framework::getInstance()->getDriver()->getSwooleServer();
            $server->on('start', function (Server $server) {
                if (!Framework::getInstance()->getArgv()['disable-safe-exit']) {
                    SignalListener::getInstance()->signalMaster();
                }
                ProcessStateManager::saveProcessState(ONEBOT_PROCESS_MASTER, $server->master_pid, [
                    'stdout' => config('global.swoole_options.swoole_set.log_file'),
                    'daemon' => (bool) Framework::getInstance()->getArgv()['daemon'],
                ]);
            });
            $server->on('shutdown', [MasterEventListener::getInstance(), 'onMasterStop']);
        } else {
            if (!Framework::getInstance()->getArgv()['disable-safe-exit'] && PHP_OS_FAMILY !== 'Windows') {
                SignalListener::getInstance()->signalMaster();
            }
            if (PHP_OS_FAMILY !== 'Windows' && extension_loaded('posix')) {
                ProcessStateManager::saveProcessState(ONEBOT_PROCESS_MASTER, posix_getpid(), [
                    'stdout' => null,
                    'daemon' => (bool) Framework::getInstance()->getArgv()['daemon'],
                ]);
                Worker::$onMasterStop = [MasterEventListener::getInstance(), 'onMasterStop'];
            }
        }
    }

    /**
     * @throws ZMKnownException
     */
    public function onMasterStop()
    {
        if (extension_loaded('posix')) {
            logger()->debug('正在关闭 Master 进程，pid=' . posix_getpid());
            ProcessStateManager::removeProcessState(ZM_PROCESS_MASTER);
            if (FileSystem::scanDirFiles(ZM_PID_DIR) == []) {
                rmdir(ZM_PID_DIR);
            }
        }
    }
}
