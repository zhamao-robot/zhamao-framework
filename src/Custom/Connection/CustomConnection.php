<?php


namespace Custom\Connection;


use ZM\Connection\WSConnection;

class CustomConnection extends WSConnection
{
    public function getType() {
        return "custom";
    }
}