<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/11/26
 * Time: 10:01 PM
 */

class CustomWSConnection extends WSConnection
{
    public function __construct(swoole_websocket_server $server, $fd, $request) {
        parent::__construct($server, $fd, $request->server["remote_addr"]);
        $this->create_success = true;
        // Here to put your custom other websocket connection to manage.
    }
}