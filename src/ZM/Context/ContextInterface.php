<?php


namespace ZM\Context;


use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use ZM\Http\Response;

interface ContextInterface
{
    public function __construct($param, $cid);

    /** @return Server */
    public function getServer();

    /** @return Frame */
    public function getFrame();

    /** @return mixed */
    public function getData();

    /** @return int */
    public function getCid();

    /** @return Response */
    public function getResponse();

    /** @return Request */
    public function getRequest();
}
