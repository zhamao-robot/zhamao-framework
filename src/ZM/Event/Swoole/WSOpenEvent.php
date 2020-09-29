<?php


namespace ZM\Event\Swoole;


use Closure;
use Doctrine\Common\Annotations\AnnotationException;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ConnectionObject;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use Swoole\Http\Request;
use Swoole\WebSocket\Server;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Annotation\Swoole\SwooleEvent;
use ZM\Event\EventHandler;
use ZM\Store\ZMBuf;

class WSOpenEvent implements SwooleEventInterface
{
    /**
     * @var Server
     */
    private $server;
    /**
     * @var Request
     */
    private $request;
    /** @var ConnectionObject */
    private $conn;

    public function __construct(Server $server, Request $request) {
        $this->server = $server;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     * @throws AnnotationException
     */
    public function onActivate() {
        $type = strtolower($this->request->get["type"] ?? $this->request->header["x-client-role"] ?? "");
        $type_conn = $this->getTypeClassName($type);
        ManagerGM::pushConnect($this->request->fd, $type_conn);
        if ($type_conn == "qq") {
            ManagerGM::setName($this->request->fd, "qq");
            $qq = $this->request->get["qq"] ?? $this->request->header["x-self-id"] ?? "";
            $self_token = ZMConfig::get("global", "access_token") ?? "";
            if (isset($this->request->header["authorization"])) {
                Console::debug($this->request->header["authorization"]);
            }
            $remote_token = $this->request->get["token"] ?? (isset($this->request->header["authorization"]) ? explode(" ", $this->request->header["authorization"])[1] : "");
            if ($qq != "" && ($self_token == $remote_token)) {
                ManagerGM::setOption($this->request->fd, "connect_id", $qq);
                $this->conn = ManagerGM::get($this->request->fd);
            } else {
                $this->conn = ManagerGM::get($this->request->fd);
                Console::warning("connection of CQ has invalid QQ or token!");
                Console::debug("Remote token: " . $remote_token);
            }
        } else {
            $this->conn = ManagerGM::get($this->request->fd);
        }
        set_coroutine_params(["server" => $this->server, "request" => $this->request, "connection" => $this->conn]);
        foreach (ZMBuf::$events[SwooleEvent::class] ?? [] as $v) {
            if (strtolower($v->type) == "open" && $this->parseSwooleRule($v) === true) {
                $c = $v->class;
                EventHandler::callWithMiddleware(
                    $c,
                    $v->method,
                    ["server" => $this->server, "request" => $this->request, "connection" => $this->conn],
                    [$this->conn]
                );
                if (context()->getCache("block_continue") === true) break;
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function onAfter() {
        if (!$this->server->exists($this->conn->getFd())) return $this;
        foreach (ZMBuf::$events[SwooleEventAfter::class] ?? [] as $v) {
            if (strtolower($v->type) == "open" && $this->parseSwooleRule($v) === true) {
                $class = new $v["class"]();
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
