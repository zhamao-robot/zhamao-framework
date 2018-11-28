<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/11/26
 * Time: 9:30 AM
 */

abstract class ServerEvent extends Event
{
    public $server;

    public function __construct(\swoole_server $server) {
        $this->server = $server;
    }
}