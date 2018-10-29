<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午4:14
 */

class WSCloseEvent extends Event
{
    public function __construct(swoole_server $server, int $fd) {
        $connect = CQUtil::getConnection($fd);
        if ($connect !== null)
            unset(Buffer::$connect[$fd]);
        Console::info("WebSocket Connection closed. fd: " . $fd);
    }
}