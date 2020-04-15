<?php


namespace ZM\Context;


use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use swoole_server;
use ZM\Http\Response;

class Context implements ContextInterface
{
    private $server = null;
    private $frame = null;
    private $data = null;
    private $request = null;
    private $response = null;
    private $cid;

    public function __construct($param, $cid) {
        if (isset($param["server"])) $this->server = $param["server"];
        if (isset($param["frame"])) $this->frame = $param["frame"];
        if (isset($param["data"])) $this->data = $param["data"];
        if (isset($param["request"])) $this->request = $param["request"];
        if (isset($param["response"])) $this->response = $param["response"];
        $this->cid = $cid;
    }

    /**
     * @return swoole_server|null
     */
    public function getServer() {
        return $this->server;
    }

    /**
     * @return Frame|null
     */
    public function getFrame() {
        return $this->frame;
    }

    /**
     * @return array|null
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return Request|null
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @return Response|null
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * @return int|null
     */
    public function getCid() {
        return $this->cid;
    }
}
