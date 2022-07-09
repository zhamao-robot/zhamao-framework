<?php

/**
 * @noinspection PhpUnusedParameterInspection
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

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
use ZM\Utils\Manager\ProcessManager;
use ZM\Utils\SignalListener;
use ZM\Utils\Terminal;
use ZM\Utils\ZMUtil;

/**
 * Class OnManagerStart
 * @SwooleHandler("ManagerStart")
 */
class OnManagerStart implements SwooleEvent
{
    private static $last_hash = '';

    private static $watch_tick_id = -1;

    public static function getWatchTickId(): int
    {
        return self::$watch_tick_id;
    }

    public function onCall(Server $server)
    {
        logger()->debug('Calling onManagerStart event(1)');
        if (!Framework::$argv['disable-safe-exit']) {
            SignalListener::signalManager();
        }
        ProcessManager::saveProcessState(ZM_PROCESS_MANAGER, $server->manager_pid);

        ProcessManager::createUserProcess('monitor', function () use ($server) {
            Process::signal(SIGINT, function () {
                Console::success('用户进程检测到了Ctrl+C');
            });
            if (Framework::$argv['watch']) {
                if (extension_loaded('inotify')) {
                    logger()->info('Enabled File watcher, framework will reload automatically.');
                    $fd = inotify_init();
                    $this->addWatcher(DataProvider::getSourceRootDir() . '/src', $fd);
                    Event::add($fd, function () use ($fd) {
                        $r = inotify_read($fd);
                        logger()->debug('File updated: ' . $r[0]['name']);
                        ZMUtil::reload();
                    });
                    Framework::$argv['polling-watch'] = false; // 如果开启了inotify则关闭轮询热更新
                } else {
                    logger()->warning(zm_internal_errcode('E00024') . '你还没有安装或启用 inotify 扩展，将默认使用轮询检测模式开启热更新！');
                    Framework::$argv['polling-watch'] = true;
                }
            }
            if (Framework::$argv['polling-watch']) {
                self::$watch_tick_id = swoole_timer_tick(3000, function () use ($server) {
                    $data = (DataProvider::scanDirFiles(DataProvider::getSourceRootDir() . '/src/'));
                    $hash = md5('');
                    foreach ($data as $file) {
                        $hash = md5($hash . md5_file($file));
                    }
                    if (self::$last_hash == '') {
                        self::$last_hash = $hash;
                    } elseif (self::$last_hash !== $hash) {
                        self::$last_hash = $hash;
                        $server->reload();
                    }
                });
            }
            if (Framework::$argv['interact']) {
                logger()->info('Interact mode');
                ZMBuf::$terminal = $r = STDIN;
                Event::add($r, function () use ($r) {
                    $fget = fgets($r);
                    if ($fget === false) {
                        Event::del($r);
                        return;
                    }
                    $var = trim($fget);
                    if ($var == 'stop') {
                        Event::del($r);
                    }
                    try {
                        Terminal::executeCommand($var);
                    } catch (Exception $e) {
                        Console::error(zm_internal_errcode('E00025') . 'Uncaught exception ' . get_class($e) . ': ' . $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')');
                    } catch (Error $e) {
                        Console::error(zm_internal_errcode('E00025') . 'Uncaught error ' . get_class($e) . ': ' . $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')');
                    }
                });
            }
        });

        ProcessManager::getUserProcess('monitor')->set(['enable_coroutine' => true]);
        ProcessManager::getUserProcess('monitor')->start();

        /*$dispatcher = new EventDispatcher(OnManagerStartEvent::class);
        $dispatcher->setRuleFunction(function($v) {
            return eval("return " . $v->getRule() . ";");
        });
        $dispatcher->dispatchEvents($server);
*/
        logger()->debug('进程 Manager 已启动');
    }

    private function addWatcher($maindir, $fd)
    {
        $dir = scandir($maindir);
        if ($dir[0] == '.') {
            unset($dir[0], $dir[1]);
        }
        foreach ($dir as $subdir) {
            if (is_dir($maindir . '/' . $subdir)) {
                logger()->debug('添加监听目录：' . $maindir . '/' . $subdir);
                inotify_add_watch($fd, $maindir . '/' . $subdir, IN_ATTRIB | IN_ISDIR);
                $this->addWatcher($maindir . '/' . $subdir, $fd);
            }
        }
    }
}
