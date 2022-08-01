<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Driver\Workerman\Worker;
use OneBot\Util\Singleton;
use Swoole\Process;
use Workerman\Events\EventInterface;
use ZM\Framework;
use ZM\Store\FileSystem;

class SignalListener
{
    use Singleton;

    /**
     * 用于记录 Ctrl+C 的次数
     *
     * @var int
     */
    private static $manager_kill_time = 0;

    /**
     * 开启 Worker 进程的 SIGINT 监听
     *
     * Workerman 为了实现 SIGUSR1 重启进程，需要额外在这里监听一遍 SIGUSR1 信号
     */
    public function signalWorker()
    {
        switch (Framework::getInstance()->getDriver()->getName()) {
            case 'swoole':
                Process::signal(SIGINT, [$this, 'onWorkerInt']);
                break;
            case 'workerman':
                if (!extension_loaded('pcntl')) {
                    logger()->error('请安装 PCNTL 扩展以支持 SIGINT 监听');
                    break;
                }
                pcntl_signal(SIGINT, [$this, 'onWorkerInt']);
                pcntl_signal(SIGUSR1, SIG_IGN, false);
                Worker::$globalEvent->add(SIGUSR1, EventInterface::EV_SIGNAL, '\Workerman\Worker::signalHandler');
                break;
        }
    }

    public function onWorkerInt()
    {
        // logger()->notice('Worker received SIGINT');
    }

    public function signalMaster()
    {
        $driver = Framework::getInstance()->getDriver()->getName();
        if ($driver === 'swoole') {
            Process::signal(SIGINT, function () {
                echo "\r";
                logger()->notice('Master 进程收到中断信号 SIGINT');
                logger()->notice('正在停止服务器');
                Framework::getInstance()->stop();
                if (extension_loaded('posix')) {
                    Process::kill(posix_getpid(), SIGTERM);
                } else {
                    /* @phpstan-ignore-next-line */
                    Process::kill(Framework::getInstance()->getDriver()->getSwooleServer()->master_pid, SIGTERM);
                }
            });
        } elseif ($driver === 'workerman') {
            if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
                logger()->error('请安装 pcntl 和 posix 扩展以支持 SIGINT 监听');
                return;
            }

            pcntl_signal(SIGUSR1, function () {
                logger()->warning('重启ing');
                Worker::reloadSelf();
            }, false);
            pcntl_signal(SIGTERM, function () {
                Worker::stopAll();
            }, false);
            pcntl_signal(SIGINT, function () {
                echo "\r";
                logger()->notice('Master 进程收到中断信号 SIGINT');
                logger()->notice('正在停止服务器');
                Worker::stopAll();
            }, false);
        }
    }

    public function signalManager()
    {
        /** @phpstan-ignore-next-line */
        $server = Framework::getInstance()->getDriver()->getSwooleServer();
        $func = function () use ($server) {
            if ($server->master_pid == $server->manager_pid) {
                echo "\r";
                logger()->notice('Manager 进程收到中断信号 SIGINT');
                swoole_timer_after(2, function () {
                    /* @noinspection PhpComposerExtensionStubsInspection */
                    Process::kill(posix_getpid(), SIGTERM);
                });
            } else {
                logger()->debug('Manager 已中断');
            }
            $this->processKillerPrompt();
        };
        logger()->debug('正在监听 Manager 进程 SIGINT');
        if (version_compare(SWOOLE_VERSION, '4.6.7') >= 0) {
            Process::signal(SIGINT, $func);
        } elseif (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, $func);
        }
    }

    /**
     * 按5次Ctrl+C后强行杀死框架的处理函数
     */
    private function processKillerPrompt()
    {
        if (self::$manager_kill_time > 0) {
            if (self::$manager_kill_time >= 5) {
                $file_path = ZM_PID_DIR;
                $flist = FileSystem::scanDirFiles($file_path, false, true);
                foreach ($flist as $file) {
                    $name = explode('.', $file);
                    if (end($name) == 'pid' && $name[0] !== 'manager') {
                        $pid = file_get_contents($file_path . '/' . $file);
                        Process::kill((int) $pid, SIGKILL);
                    }
                    unlink($file_path . '/' . $file);
                }
            } else {
                echo "\r";
                logger()->notice('请再按 {count} 次 Ctrl+C 以强制杀死所有 Worker 进程', ['count' => 5 - self::$manager_kill_time]);
            }
        }
        ++self::$manager_kill_time;
    }
}
