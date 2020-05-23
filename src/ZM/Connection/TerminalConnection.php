<?php


namespace ZM\Connection;


class TerminalConnection extends WSConnection
{

    public function getType() {
        return "terminal";
    }
}
