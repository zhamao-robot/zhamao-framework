<?php


namespace ZM\Event\SwooleEvent;


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
        Console::verbose("进程 Manager 已停止！");
    }
}