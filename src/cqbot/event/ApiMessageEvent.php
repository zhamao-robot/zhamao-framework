<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午4:01
 */

class ApiMessageEvent extends Event
{
    public function __construct(swoole_http_client $client, swoole_websocket_frame $frame) {
        $res = json_decode($frame->data, true);
        if (isset($res["echo"])) APIHandler::execute($res["echo"], $res);
    }
}