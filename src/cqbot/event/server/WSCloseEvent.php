<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午4:14
 */

class WSCloseEvent extends ServerEvent
{
    public function __construct(swoole_server $server, int $fd) {
        parent::__construct($server);
        $connect = ConnectionManager::get($fd);
        if ($connect !== null) {
            ConnectionManager::remove($fd);
            Console::info("WebSocket Connection closed. fd: " . $fd);
        } else {
            Console::info("Unknown WS or HTTP Connection closed. fd: ".$fd);
        }
    }
}