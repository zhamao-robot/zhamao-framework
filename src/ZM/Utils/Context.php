<?php


namespace ZM\Utils;


use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use swoole_server;
use ZM\Http\Response;

class Context
{
    private $server = null;
    private $frame = null;
    private $data = null;
    private $request = null;
    private $response = null;
    private $cid;

    public function __construct($param0, $cid) {
        if (isset($param0["server"])) $this->server = $param0["server"];
        if (isset($param0["frame"])) $this->frame = $param0["frame"];
        if (isset($param0["data"])) $this->data = $param0["data"];
        if (isset($param0["request"])) $this->request = $param0["request"];
        if (isset($param0["response"])) $this->response = $param0["response"];
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
