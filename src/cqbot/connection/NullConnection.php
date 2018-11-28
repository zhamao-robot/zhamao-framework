<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/11/18
 * Time: 11:11 AM
 */

class NullConnection extends WSConnection
{
    private $qq = 0;

    public function __construct(swoole_websocket_server $server, $fd, $remote, $qq = 0) {
        parent::__construct($server, $fd, $remote);
        $this->qq = $qq;
    }

    public function push($data, $push_error_record = true) {
        $data = unicodeDecode($data);
        CQUtil::errorlog("API推送失败，未发送的消息: \n" . $data, "API ERROR", false);
        if ($push_error_record) Cache::append("bug_msg_list", json_decode($data, true));
        return false;
    }

    public function sendAPI($api, $params = [], $echo = []){
        $data["action"] = $api;
        if($params != []) $data["params"] = $params;
        $echo_result["self_id"] = $this->qq;
        if($echo != []){
            if(count($echo) >= 1) $echo_result['type'] = array_shift($echo);
            if(!empty($echo)) $echo_result["params"] = $echo;
        }
        $data["echo"] = $echo_result;
        Console::debug("将要发送的API包：" . json_encode($data, 128 | 256));
        return $this->push(json_encode($data));
    }
}