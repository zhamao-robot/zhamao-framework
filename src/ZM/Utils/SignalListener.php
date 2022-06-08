<?php

declare(strict_types=1);

namespace ZM\Utils;

use Swoole\Process;
use Swoole\Server;

/**
 * 炸毛框架的Linux signal管理类
 * Class SignalListener
 *
 * @since 2.5
 */
class SignalListener
{
    private static $manager_kill_time = 0;

    /**
     * 监听Master进程的Ctrl+C
     */
    public static function signalMaster(Server $server)
    {
        logger()->debug('正在监听 Master 进程 SIGINT');
        Process::signal(SIGINT, function () use ($server) {
            if (zm_atomic('_int_is_reload')->get() === 1) {
                zm_atomic('_int_is_reload')->set(0);
                $server->reload();
            } else {
                echo "\r";
                logger()->notice('Master 进程收到中断信号 SIGINT');
                logger()->notice('正在停止服务器');
                Process::kill($server->master_pid, SIGTERM);
            }
        });
    }

    /**
     * 监听Manager进程的Ctrl+C
     */
    public static function signalManager()
    {
        $func = function () {
            if (\server()->master_pid == \server()->manager_pid) {
                echo "\r";
                logger()->notice('Manager 进程收到中断信号 SIGINT');
                swoole_timer_after(2, function () {
                    Process::kill(posix_getpid(), SIGTERM);
                });
            } else {
                logger()->debug('Manager 已中断');
            }
            self::processKillerPrompt();
        };
        logger()->debug('正在监听 Manager 进程 SIGINT');
        if (version_compare(SWOOLE_VERSION, '4.6.7') >= 0) {
            Process::signal(SIGINT, $func);
        } elseif (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, $func);
        }
    }

    /**
     * 监听Worker/TaskWorker进程的Ctrl+C
     *
     * @param int $worker_id 当前进程的ID
     */
    public static function signalWorker(Server $server, int $worker_id)
    {
        logger()->debug('正在监听 Worker#{worker_id} 进程 SIGINT', compact('worker_id'));
        Process::signal(SIGINT, function () use ($server) {
            if ($server->master_pid === $server->worker_pid) { // 当Swoole以单进程模型运行的时候，Worker需要监听杀死的信号
                echo "\r";
                logger()->notice('Worker 进程收到中断信号 SIGINT');
                swoole_timer_after(2, function () {
                    Process::kill(posix_getpid(), SIGTERM);
                });
                self::processKillerPrompt();
            }
            // logger()->debug("Interrupted in worker");
            // do nothing
        });
    }

    /**
     * 按5次Ctrl+C后强行杀死框架的处理函数
     */
    private static function processKillerPrompt()
    {
        if (self::$manager_kill_time > 0) {
            if (self::$manager_kill_time >= 5) {
                $file_path = _zm_pid_dir();
                $flist = DataProvider::scanDirFiles($file_path, false, true);
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
