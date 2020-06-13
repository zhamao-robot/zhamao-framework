<?php


namespace ZM\Event\Swoole;


use Closure;
use Doctrine\Common\Annotations\AnnotationException;
use Framework\ZMBuf;
use Swoole\Server;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Connection\ConnectionManager;
use ZM\Event\EventHandler;
use ZM\Utils\ZMUtil;

class WSCloseEvent implements SwooleEvent
{
    public $server;

    public $fd;

    public function __construct(Server $server, int $fd) {
        $this->server = $server;
        $this->fd = $fd;
    }

    /**
     * @inheritDoc
     * @throws AnnotationException
     */
    public function onActivate() {
        ZMUtil::checkWait();
        set_coroutine_params(["server" => $this->server, "fd" => $this->fd, "connection" => ConnectionManager::get($this->fd)]);
        foreach(ZMBuf::$events[SwooleEventAt::class] ?? [] as $v) {
            if(strtolower($v->type) == "close" && $this->parseSwooleRule($v)) {
                $c = $v->class;
                EventHandler::callWithMiddleware($c, $v->method, ["server" => $this->server, "fd" => $this->fd], []);
                if(context()->getCache("block_continue") === true) break;
            }
        }
        ConnectionManager::close($this->fd);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws AnnotationException
     */
    public function onAfter() {
        foreach (ZMBuf::$events[SwooleEventAfter::class] ?? [] as $v) {
            if (strtolower($v->type) == "close" && $this->parseSwooleRule($v) === true) {
                $c = $v->class;
                EventHandler::callWithMiddleware($c, $v->method, ["server" => $this->server, "fd" => $this->fd], []);
                if(context()->getCache("block_continue") === true) break;
            }
        }
        return $this;
    }

    private function parseSwooleRule($v) {
        switch (explode(":", $v->rule)[0]) {
            case "connectType": //websocket连接类型
                if ($v->callback instanceof Closure) return call_user_func($v->callback, ConnectionManager::get($this->fd));
                break;
        }
        return true;
    }
}
