<?php


namespace ZM\Event\Swoole;


use Closure;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Annotation\Swoole\SwooleEvent;
use Exception;
use ZM\Event\EventHandler;
use ZM\Store\ZMBuf;

class MessageEvent implements SwooleEventInterface
{
    /**
     * @var Server
     */
    public $server;
    /**
     * @var Frame
     */
    public $frame;

    public function __construct(Server $server, Frame $frame) {
        $this->server = $server;
        $this->frame = $frame;
    }

    /**
     * @inheritDoc
     */
    public function onActivate() {
        $conn = ManagerGM::get(context()->getFrame()->fd);
        try {
            if ($conn->getName() == "qq") {
                $data = json_decode(context()->getFrame()->data, true);
                if (isset($data["post_type"])) {
                    set_coroutine_params(["data" => $data, "connection" => $conn]);
                    ctx()->setCache("level", 0);
                    Console::debug("Calling CQ Event from fd=" . $conn->getFd());
                    EventHandler::callCQEvent($data, ManagerGM::get(context()->getFrame()->fd), 0);
                } else{
                    set_coroutine_params(["connection" => $conn]);
                    EventHandler::callCQResponse($data);
                }
            }
            foreach (ZMBuf::$events[SwooleEvent::class] ?? [] as $v) {
                if (strtolower($v->type) == "message" && $this->parseSwooleRule($v)) {
                    $c = $v->class;
                    EventHandler::callWithMiddleware(
                        $c,
                        $v->method,
                        ["server" => $this->server, "frame" => $this->frame, "connection" => $conn],
                        [$conn]
                    );
                    if (context()->getCache("block_continue") === true) break;
                }
            }
        } catch (Exception $e) {
            Console::warning("Websocket message event exception: " . (($cs = $e->getMessage()) == "" ? get_class($e) : $cs));
            Console::warning("In ". $e->getFile() . " at line ".$e->getLine());
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function onAfter() {
        foreach (ZMBuf::$events[SwooleEventAfter::class] ?? [] as $v) {
            if (strtolower($v->type) == "message" && $this->parseSwooleRule($v) === true) {
                $c = $v->class;
                $class = new $c();
                call_user_func_array([$class, $v->method], []);
                if (context()->getCache("block_continue") === true) break;
            }
        }
        return $this;
    }

    private function parseSwooleRule($v) {
        switch (explode(":", $v->rule)[0]) {
            case "connectType": //websocket连接类型
                if ($v->callback instanceof Closure) return call_user_func($v->callback, ManagerGM::get($this->frame->fd));
                break;
            case "dataEqual": //handle websocket message事件时才能用
                if ($v->callback instanceof Closure) return call_user_func($v->callback, $this->frame->data);
                break;
        }
        return true;
    }
}
