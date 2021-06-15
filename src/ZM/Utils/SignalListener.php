<?php


namespace ZM\Utils;


use Swoole\Event;
use Swoole\Process;
use Swoole\Server;
use Swoole\Timer;
use ZM\Console\Console;
use ZM\Framework;
use ZM\Store\ZMBuf;
use ZM\Utils\Manager\ProcessManager;

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
        Process::signal(SIGINT, function () use ($server) {
            if (zm_atomic("_int_is_reload")->get() === 1) {
                zm_atomic("_int_is_reload")->set(0);
                $server->reload();
            } else {
                echo "\r";
                Console::warning("Server interrupted(SIGINT) on Master.");
                if ((Framework::$server->inotify ?? null) !== null)
                    /** @noinspection PhpUndefinedFieldInspection */ Event::del(Framework::$server->inotify);
                if (ZMBuf::$terminal !== null)
                    Event::del(ZMBuf::$terminal);
                Process::kill($server->master_pid, SIGTERM);
            }
        });
    }

    /**
     * 监听Manager进程的Ctrl+C
     */
    public static function signalManager() {
        $func = function () {
            Console::verbose("Interrupted in manager!");
        };
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
        Process::signal(SIGINT, function () use ($worker_id, $server) {
            // do nothing
        });
        if ($server->taskworker === false) {
            Process::signal(SIGUSR1, function () use ($worker_id) {
                Timer::clearAll();
                ProcessManager::resumeAllWorkerCoroutines();
            });
        }
    }
}