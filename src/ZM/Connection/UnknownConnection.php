<?php


namespace ZM\Connection;


class UnknownConnection extends WSConnection
{

    public function getType() {
        return "unknown";
    }
}