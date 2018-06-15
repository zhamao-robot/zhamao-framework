<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午4:10
 */

class WSOpenEvent extends Event
{
    public function __construct(swoole_websocket_server $server, swoole_http_request $request) {
        $fd = $request->fd;
        //Console::info("收到连接：".$fd);
        CQUtil::getConnection($fd);
    }
}