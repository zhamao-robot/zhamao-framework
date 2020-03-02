<?php


namespace ZM\Connection;


class ProxyConnection extends WSConnection
{

    public function getType() {
        return "proxy";
    }
}