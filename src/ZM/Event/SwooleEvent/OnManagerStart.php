<?php /** @noinspection PhpUnusedParameterInspection */

/** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Event\SwooleEvent;


use Error;
use Exception;
use Swoole\Event;
use Swoole\Process;
use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Framework;
use ZM\Store\ZMBuf;
use ZM\Utils\DataProvider;
use ZM\Utils\SignalListener;
use ZM\Utils\Terminal;
use ZM\Utils\ZMUtil;

/**
 * Class OnManagerStart
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("ManagerStart")
 */
class OnManagerStart implements SwooleEvent
{
    /** @var null|Process */
    public static $process = null;

    public function onCall(Server $server) {
        Console::debug("Calling onManagerStart event(1)");
        if (!Framework::$argv["disable-safe-exit"]) {
            SignalListener::signalManager();
        }
        self::$process = new Process(function() {
            if (Framework::$argv["watch"]) {
                if (extension_loaded('inotify')) {
                    Console::info("Enabled File watcher, framework will reload automatically.");
                    /** @noinspection PhpUndefinedFieldInspection */
                    Framework::$server->inotify = $fd = inotify_init();
                    $this->addWatcher(DataProvider::getSourceRootDir() . "/src", $fd);
                    Event::add($fd, function () use ($fd) {
                        $r = inotify_read($fd);
                        Console::verbose("File updated: " . $r[0]["name"]);
                        ZMUtil::reload();
                    });
                } else {
                    Console::warning(zm_internal_errcode("E00024") . "You have not loaded \"inotify\" extension, please install first.");
                }
            }
            if (Framework::$argv["interact"]) {
                Console::info("Interact mode");
                ZMBuf::$terminal = $r = STDIN;
                Event::add($r, function () use ($r) {
                    $fget = fgets($r);
                    if ($fget === false) {
                        Event::del($r);
                        return;
                    }
                    $var = trim($fget);
                    if ($var == "stop") Event::del($r);
                    try {
                        Terminal::executeCommand($var);
                    } catch (Exception $e) {
                        Console::error(zm_internal_errcode("E00025") . "Uncaught exception " . get_class($e) . ": " . $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")");
                    } catch (Error $e) {
                        Console::error(zm_internal_errcode("E00025") . "Uncaught error " . get_class($e) . ": " . $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")");
                    }
                });
            }
        });
        self::$process->set(['enable_coroutine' => true]);
        self::$process->start();
        Console::verbose("进程 Manager 已启动");
    }

    private function addWatcher($maindir, $fd) {
        $dir = scandir($maindir);
        if ($dir[0] == ".") {
            unset($dir[0], $dir[1]);
        }
        foreach ($dir as $subdir) {
            if (is_dir($maindir . "/" . $subdir)) {
                Console::debug("添加监听目录：" . $maindir . "/" . $subdir);
                inotify_add_watch($fd, $maindir . "/" . $subdir, IN_ATTRIB | IN_ISDIR);
                $this->addWatcher($maindir . "/" . $subdir, $fd);
            }
        }
    }
}