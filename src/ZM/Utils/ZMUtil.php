<?php


namespace ZM\Utils;


use Exception;
use Swoole\Process;
use ZM\Console\Console;
use ZM\Framework;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Store\ZMBuf;

class ZMUtil
{
    /**
     * @throws Exception
     */
    public static function stop() {
        if (SpinLock::tryLock("_stop_signal") === false) return;
        Console::warning(Console::setColor("Stopping server...", "red"));
        if (Console::getLevel() >= 4) Console::trace();
        ZMAtomic::get("stop_signal")->set(1);
        for($i = 0; $i < ZM_WORKER_NUM; ++$i) {
            Process::kill(zm_atomic("_#worker_".$i)->get(), SIGUSR1);
        }
        server()->shutdown();
        server()->stop();
    }

    /**
     * @throws Exception
     */
    public static function reload() {
        zm_atomic("_int_is_reload")->set(1);
        system("kill -INT " . intval(server()->master_pid));
    }

    public static function getModInstance($class) {
        if (!isset(ZMBuf::$instance[$class])) {
            //Console::debug("Class instance $class not exist, so I created it.");
            return ZMBuf::$instance[$class] = new $class();
        } else {
            return ZMBuf::$instance[$class];
        }
    }

    public static function sendActionToWorker($target_id, $action, $data) {
        Console::verbose($action . ": " . $data);
        server()->sendMessage(json_encode(["action" => $action, "data" => $data]), $target_id);
    }

    /**
     * 在工作进程中返回可以通过reload重新加载的php文件列表
     * @return string[]|string[][]
     */
    public static function getReloadableFiles() {
        return array_map(
            function ($x) {
                return str_replace(DataProvider::getWorkingDir() . "/", "", $x);
            }, array_diff(
                get_included_files(),
                Framework::$loaded_files
            )
        );
    }
}
