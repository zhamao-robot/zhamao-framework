<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/6/13
 * Time: 8:10 PM
 */

class WSConnection
{
    public $fd;

    /**
     * 0 = event连接
     * 1 = api连接
     * 默认为event连接，如果可以收到返回的get_status则标记为1
     * @var int
     */
    protected $type = 0;

    protected $server;

    protected $qq = "";

    public $create_success = false;

    public function __construct(swoole_websocket_server $server, $fd, $type, $qq) {
        $this->server = $server;
        $this->fd = $fd;
        if ($type = null || $qq === null) return;
        $this->type = ($type == "event" ? 0 : 1);
        $this->qq = $qq;
        $this->create_success = true;
    }

    /**
     * 返回swoole server
     * @return swoole_websocket_server
     */
    public function getServer() {
        return $this->server;
    }

    /**
     * 返回本连接是什么类型的
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    public function initConnection() {
        foreach (CQUtil::getConnections(($this->getType() == 0 ? "event" : "api")) as $k => $v) {
            if ($v->getQQ() == $this->getQQ() && $k != $this->fd) {
                $this->server->close($k);
                unset(Buffer::$connect[$k]);
            }
        }
        if ($this->type === 1) {
            new APIConnectEvent($this, $this->server);
        }
    }

    /**
     * @return string
     */
    public function getQQ() {
        return $this->qq;
    }
}