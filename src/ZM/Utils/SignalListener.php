<?php


namespace ZM\Utils;


use Swoole\Process;
use Swoole\Server;
use ZM\Console\Console;

/**
 * 炸毛框架的Linux signal管理类
 * Class SignalListener
 * @package ZM\Utils
 * @since 2.5
 */
class SignalListener
{
    /**
     * 监听Master进程的Ctrl+C
     * @param Server $server
     */
    public static function signalMaster(Server $server) {
        Console::debug("Listening Master SIGINT");
        Process::signal(SIGINT, function () use ($server) {
            if (zm_atomic("_int_is_reload")->get() === 1) {
                zm_atomic("_int_is_reload")->set(0);
                $server->reload();
            } else {
                echo "\r";
                Console::warning("Server interrupted(SIGINT) on Master.");
                Process::kill($server->master_pid, SIGTERM);
            }
        });
    }

    /**
     * 监听Manager进程的Ctrl+C
     */
    public static function signalManager() {
        $func = function () {
            if (\server()->master_pid == \server()->manager_pid) {
                echo "\r";
                Console::warning("Server interrupted(SIGINT) on Manager.");
                swoole_timer_after(2, function() {
                    Process::kill(posix_getpid(), SIGTERM);
                });
            } else {
                Console::verbose("Interrupted in manager!");
            }
        };
        Console::debug("Listening Manager SIGINT");
        if (version_compare(SWOOLE_VERSION, "4.6.7") >= 0) {
            Process::signal(SIGINT, $func);
        } elseif (extension_loaded("pcntl")) {
            pcntl_signal(SIGINT, $func);
        }
    }

    /**
     * 监听Worker/TaskWorker进程的Ctrl+C
     * @param Server $server
     * @param $worker_id
     */
    public static function signalWorker(Server $server, $worker_id) {
        Console::debug("Listening Worker #".$worker_id." SIGINT");
        Process::signal(SIGINT, function () use ($worker_id, $server) {
            if ($server->master_pid == $server->worker_pid) {
                echo "\r";
                Console::warning("Server interrupted(SIGINT) on Worker.");
                swoole_timer_after(2, function() {
                    Process::kill(posix_getpid(), SIGTERM);
                });
            }
            //Console::verbose("Interrupted in worker");
            // do nothing
        });
    }
}