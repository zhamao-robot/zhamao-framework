<?php


namespace ZM\Event\Swoole;


use Framework\ZMBuf;
use Swoole\Server;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Connection\ConnectionManager;
use ZM\ModBase;
use ZM\ModHandleType;
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
     */
    public function onActivate() {
        ZMUtil::checkWait();
        ConnectionManager::close($this->fd);
        foreach(ZMBuf::$events[SwooleEventAt::class] ?? [] as $v) {
            if(strtolower($v->type) == "close" && $this->parseSwooleRule($v)) {
                $c = $v->class;
                $class = new $c(["server" => $this->server, "fd" => $this->fd], ModHandleType::SWOOLE_CLOSE);
                call_user_func_array([$class, $v->method], []);
                if($class->block_continue) break;
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function onAfter() {
        foreach (ZMBuf::$events[SwooleEventAfter::class] ?? [] as $v) {
            if (strtolower($v->type) == "close" && $this->parseSwooleRule($v) === true) {
                $c = $v->class;
                /** @var ModBase $class */
                $class = new $c(["server" => $this->server, "fd" => $this->fd], ModHandleType::SWOOLE_CLOSE);
                call_user_func_array([$class, $v->method], []);
                if($class->block_continue) break;
            }
        }
        return $this;
    }

    private function parseSwooleRule($v) {
        return true;
    }
}