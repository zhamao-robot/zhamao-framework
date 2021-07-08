<?php


namespace ZM\Event\SwooleEvent;


use Swoole\Process;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;

/**
 * Class OnManagerStop
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("ManagerStop")
 */
class OnManagerStop implements SwooleEvent
{
    public function onCall() {
        if (OnManagerStart::$process !== null) {
            if (Process::kill(OnManagerStart::$process->pid, 0)) {
                Process::kill(OnManagerStart::$process->pid, SIGTERM);
            }
        }
        Console::verbose("进程 Manager 已停止！");
    }
}