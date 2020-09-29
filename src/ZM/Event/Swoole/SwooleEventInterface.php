<?php


namespace ZM\Event\Swoole;


use ZM\Event\Event;

interface SwooleEventInterface extends Event
{
    /**
     * @return SwooleEventInterface
     */
    public function onActivate();

    /**
     * @return SwooleEventInterface
     */
    public function onAfter();
}
