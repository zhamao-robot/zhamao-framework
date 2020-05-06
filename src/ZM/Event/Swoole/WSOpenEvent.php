<?php


namespace ZM\Event\Swoole;


use Closure;
use Framework\ZMBuf;
use Swoole\Http\Request;
use Swoole\WebSocket\Server;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Connection\ConnectionManager;
use ZM\Connection\CQConnection;
use ZM\Connection\UnknownConnection;
use ZM\Connection\WSConnection;
use ZM\ModBase;
use ZM\ModHandleType;
use ZM\Utils\ZMUtil;

class WSOpenEvent implements SwooleEvent
{
    /**
     * @var Server
     */
    private $server;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var WSConnection
     */
    private $conn;

    public function __construct(Server $server, Request $request) {
        $this->server = $server;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function onActivate() {
        ZMUtil::checkWait();
        $type = strtolower($this->request->get["type"] ?? $this->request->header["x-client-role"] ?? "");
        $type_conn = ConnectionManager::getTypeClassName($type);
        if ($type_conn == CQConnection::class) {
            $qq = $this->request->get["qq"] ?? $this->request->header["x-self-id"] ?? "";
            $self_token = ZMBuf::globals("access_token") ?? "";
            $remote_token = $this->request->get["token"] ?? (isset($header["authorization"]) ? explode(" ", $this->request->header["authorization"])[1] : "");
            if ($qq != "" && ($self_token == $remote_token)) $this->conn = new CQConnection($this->server, $this->request->fd, $qq);
            else $this->conn = new UnknownConnection($this->server, $this->request->fd);
        } else {
            $this->conn = new $type_conn($this->server, $this->request->fd);
        }
        ZMBuf::$connect[$this->request->fd] = $this->conn;
        set_coroutine_params(["server" => $this->server, "request" => $this->request, "connection" => $this->conn]);
        foreach (ZMBuf::$events[SwooleEventAt::class] ?? [] as $v) {
            if (strtolower($v->type) == "open" && $this->parseSwooleRule($v) === true) {
                $c = $v->class;
                $class = new $c(["server" => $this->server, "request" => $this->request, "connection" => $this->conn], ModHandleType::SWOOLE_OPEN);
                call_user_func_array([$class, $v->method], [$this->conn]);
                if (context()->getCache("block_continue") === true) break;
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function onAfter() {
        if (!$this->conn->exists()) return $this;
        foreach (ZMBuf::$events[SwooleEventAfter::class] ?? [] as $v) {
            if (strtolower($v->type) == "open" && $this->parseSwooleRule($v) === true) {
                /** @var ModBase $class */
                $class = new $v["class"](["server" => $this->server, "request" => $this->request, "connection" => $this->conn], ModHandleType::SWOOLE_OPEN);
                call_user_func_array([$class, $v["method"]], [$this->conn]);
                if (context()->getCache("block_continue") === true) break;
            }
        }
        return $this;
    }

    private function parseSwooleRule($v) {
        switch (explode(":", $v->rule)[0]) {
            case "connectType": //websocket连接类型
                if ($v->callback instanceof Closure) return call_user_func($v->callback, $this->conn);
                break;
        }
        return true;
    }
}
