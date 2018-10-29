<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/10/13
 * Time: 3:53 PM
 */

class APIConnectEvent extends Event
{
    public function __construct(WSConnection $connection, swoole_websocket_server $server) {
        $alias = [
            "10001" => '小马哥'
            //这里放机器人连接的别名
            //"QQ" => "别名"
        ];
        CQUtil::sendDebugMsg("[CQBot] 机器人 " . ($alias[$connection->getQQ()] ?? $connection->getQQ()) . " 已连接", strval($connection->getQQ()), false);
        Buffer::set("admin_active", $connection->getQQ());
    }
}