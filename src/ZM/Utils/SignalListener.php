<?php

declare(strict_types=1);

namespace ZM\Utils;

use Swoole\Process;
use Swoole\Server;
use ZM\Console\Console;

/**
 * 炸毛框架的Linux signal管理类
 * Class SignalListener
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
        Console::debug('Listening Master SIGINT');
        Process::signal(SIGINT, function () use ($server) {
            if (zm_atomic('_int_is_reload')->get() === 1) {
                zm_atomic('_int_is_reload')->set(0);
                $server->reload();
            } else {
                echo "\r";
                Console::warning('Server interrupted(SIGINT) on Master.');
                Console::warning('Server will be shutdown.');
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
                Console::warning('Server interrupted(SIGINT) on Manager.');
                swoole_timer_after(2, function () {
                    Process::kill(posix_getpid(), SIGTERM);
                });
            } else {
                Console::verbose('Interrupted in manager!');
            }
            self::processKillerPrompt();
        };
        Console::debug('Listening Manager SIGINT');
        if (version_compare(SWOOLE_VERSION, '4.6.7') >= 0) {
            Process::signal(SIGINT, $func);
        } elseif (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, $func);
        }
    }

    /**
     * 监听Worker/TaskWorker进程的Ctrl+C
     * @param $worker_id
     */
    public static function signalWorker(Server $server, $worker_id)
    {
        Console::debug('Listening Worker #' . $worker_id . ' SIGINT');
        Process::signal(SIGINT, function () use ($server) {
            if ($server->master_pid == $server->worker_pid) { // 当Swoole以单进程模型运行的时候，Worker需要监听杀死的信号
                echo "\r";
                Console::warning('Server interrupted(SIGINT) on Worker.');
                swoole_timer_after(2, function () {
                    Process::kill(posix_getpid(), SIGTERM);
                });
                self::processKillerPrompt();
            }
            // Console::verbose("Interrupted in worker");
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
                Console::log('再按' . (5 - self::$manager_kill_time) . '次Ctrl+C所有Worker进程就会被强制杀死', 'red');
            }
        }
        ++self::$manager_kill_time;
    }
}
