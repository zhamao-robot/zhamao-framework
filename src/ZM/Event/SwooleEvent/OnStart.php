<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Event\SwooleEvent;


use Swoole\Event;
use Swoole\Process;
use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Utils\DataProvider;
use ZM\Utils\ZMUtil;

/**
 * Class OnStart
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("start")
 */
class OnStart implements SwooleEvent
{
    public function onCall(Server $server) {
        $r = null;
        if (!Framework::$argv["disable-safe-exit"]) {
            Process::signal(SIGINT, function () use ($r, $server) {
                if (zm_atomic("_int_is_reload")->get() === 1) {
                    zm_atomic("_int_is_reload")->set(0);
                    $server->reload();
                } else {
                    echo "\r";
                    Console::warning("Server interrupted(SIGINT) on Master.");
                    if ((Framework::$server->inotify ?? null) !== null)
                        /** @noinspection PhpUndefinedFieldInspection */ Event::del(Framework::$server->inotify);
                    Process::kill($server->master_pid, SIGTERM);
                }
            });
        }
        if (Framework::$argv["daemon"]) {
            $daemon_data = json_encode([
                "pid" => $server->master_pid,
                "stdout" => ZMConfig::get("global")["swoole"]["log_file"]
            ], 128 | 256);
            file_put_contents(DataProvider::getWorkingDir() . "/.daemon_pid", $daemon_data);
        }
        if (Framework::$argv["watch"]) {
            if (extension_loaded('inotify')) {
                Console::info("Enabled File watcher, framework will reload automatically.");
                /** @noinspection PhpUndefinedFieldInspection */
                Framework::$server->inotify = $fd = inotify_init();
                $this->addWatcher(DataProvider::getWorkingDir() . "/src", $fd);
                Event::add($fd, function () use ($fd) {
                    $r = inotify_read($fd);
                    Console::verbose("File updated: ".$r[0]["name"]);
                    ZMUtil::reload();
                });
            } else {
                Console::warning("You have not loaded \"inotify\" extension, please install first.");
            }
        }
    }

    private function addWatcher($maindir, $fd) {
        $dir = scandir($maindir);
        unset($dir[0], $dir[1]);
        foreach ($dir as $subdir) {
            if (is_dir($maindir . "/" . $subdir)) {
                Console::debug("添加监听目录：" . $maindir . "/" . $subdir);
                inotify_add_watch($fd, $maindir . "/" . $subdir, IN_ATTRIB | IN_ISDIR);
                $this->addWatcher($maindir . "/" . $subdir, $fd);
            }
        }
    }
}