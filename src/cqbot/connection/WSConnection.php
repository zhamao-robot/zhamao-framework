<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/7/27
 * Time: 12:31 PM
 */

abstract class WSConnection
{
    public $fd;

    protected $server;
    protected $remote_address;

    public $create_success = false;

    public function __construct(swoole_websocket_server $server, $fd, $remote) {
        $this->server = $server;
        $this->fd = $fd;
        $this->remote_address = $remote;
    }

    /**
     * 返回swoole server
     * @return swoole_websocket_server
     */
    public function getServer() {
        return $this->server;
    }

    public function getType(){
        if($this instanceof RobotWSConnection) return "robot";
        elseif($this instanceof CustomWSConnection) return "custom";
        else return "unknown";
    }

    public function push($data, $push_error_record = true) {
        if ($data === null || $data == "") {
            Console::error("Empty data pushed.");
            return false;
        }
        if ($this->server->push($this->fd, $data) === false) {
            $data = unicodeDecode($data);
            CQUtil::errorlog("API推送失败，未发送的消息: \n" . $data, "API ERROR", false);
            if ($push_error_record) Cache::append("bug_msg_list", json_decode($data, true));

            return false;
        }
        return true;
    }

    public function close() {
        $this->server->close($this->fd);
        ConnectionManager::remove($this->fd);
    }

    /**
     * @return mixed
     */
    public function getRemoteAddress() {
        return $this->remote_address;
    }
}