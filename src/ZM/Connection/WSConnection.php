<?php


namespace ZM\Connection;


use Framework\Console;
use swoole_websocket_server;

abstract class WSConnection
{
    public $fd;

    /** @var swoole_websocket_server */
    protected $server;

    public $available = false;

    public function __construct($server, $fd) {
        $this->server = $server;
        $this->fd = $fd;
    }

    public abstract function getType();

    public function exists() {
        return $this->available = $this->server->exist($this->fd);
    }

    public function close() {
        ConnectionManager::close($this->fd);
    }

    public function push($data, $push_error_record = true) {
        if ($data === null || $data == "") {
            Console::warning("推送了空消息");
            return false;
        }
        if (!$this->server->exist($this->fd)) {
            Console::warning("Swoole 原生 websocket连接池中无此连接");
            return false;
        }
        if ($this->server->push($this->fd, $data) === false) {
            $data = unicode_decode($data);
            if ($push_error_record) Console::warning("API push failed. Data: " . $data);
            Console::warning("websocket数据未成功推送，长度：" . strlen($data));
            return false;
        }
        return true;
    }
}
