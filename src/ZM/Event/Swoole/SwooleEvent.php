<?php


namespace ZM\Event\Swoole;


use ZM\Event\Event;

interface SwooleEvent extends Event
{
    /**
     * @return SwooleEvent
     */
    public function onActivate();

    /**
     * @return SwooleEvent
     */
    public function onAfter();
}