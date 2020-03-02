<?php


namespace Scheduler;


use Swoole\Coroutine\Http\Client;
use Swoole\WebSocket\Frame;

class MessageEvent
{
    /**
     * @var Frame
     */
    private $frame;
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client, Frame $frame) {
        $this->client = $client;
        $this->frame = $frame;
    }

    public function onActivate() {
        //TODO: 写Scheduler计时器内的处理逻辑
    }
}