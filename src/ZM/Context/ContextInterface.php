<?php


namespace ZM\Context;


use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Http\Response;
use ZM\API\ZMRobot;

interface ContextInterface
{
    public function __construct($cid);

    /** @return Server */
    public function getServer();

    /** @return Frame */
    public function getFrame();

    /** @return mixed */
    public function getData();

    public function setData($data);

    /** @return ConnectionObject */
    public function getConnection();

    /** @return int|null */
    public function getFd();

    /** @return int */
    public function getCid();

    /** @return Response */
    public function getResponse();

    /** @return Request */
    public function getRequest();

    /** @return ZMRobot */
    public function getRobot();

    /** @return mixed */
    public function getUserId();

    /** @return mixed */
    public function getGroupId();

    /** @return mixed */
    public function getDiscussId();

    /** @return string */
    public function getMessageType();

    /** @return mixed */
    public function getRobotId();

    /** @return mixed */
    public function getMessage();

    public function setMessage($msg);

    public function setUserId($id);

    public function setGroupId($id);

    public function setDiscussId($id);

    public function setMessageType($type);

    public function getCQResponse();

    /**
     * @param $msg
     * @param bool $yield
     * @return mixed
     */
    public function reply($msg, $yield = false);

    /**
     * @param $msg
     * @param bool $yield
     * @return mixed
     */
    public function finalReply($msg, $yield = false);

    /**
     * @param string $prompt
     * @param int $timeout
     * @param string $timeout_prompt
     * @return mixed
     */
    public function waitMessage($prompt = "", $timeout = 600, $timeout_prompt = "");

    /**
     * @param $mode
     * @param $prompt_msg
     * @return mixed
     */
    public function getArgs($mode, $prompt_msg);

    public function setCache($key, $value);

    /**
     * @param $key
     * @return mixed
     */
    public function getCache($key);

    public function cloneFromParent();

    public function copy();

    public function getOption();
}
