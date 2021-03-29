<?php /** @noinspection PhpUnusedParameterInspection */


namespace ZM\Event\SwooleEvent;


use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;

/**
 * Class OnShutdown
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("shutdown")
 */
class OnShutdown implements SwooleEvent
{
    public function onCall(Server $server) {
        Console::verbose("正在关闭 Master 进程，pid=" . posix_getpid());
    }
}