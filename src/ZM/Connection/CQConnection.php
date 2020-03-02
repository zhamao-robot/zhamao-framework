<?php


namespace ZM\Connection;


class CQConnection extends WSConnection
{
    public $self_id = null;

    public function __construct($server, $fd, $self_id) {
        parent::__construct($server, $fd);
        $this->self_id = $self_id;
    }

    public function getQQ(){
        return $this->self_id;
    }

    public function getType() {
        return "qq";
    }
}