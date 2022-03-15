<?php

declare(strict_types=1);

namespace ZM\Context;

use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use ZM\API\ZMRobot;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Http\Response;

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

    /** @return null|int */
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

    public function reply($msg, $yield = false);

    public function finalReply($msg, $yield = false);

    public function waitMessage($prompt = '', $timeout = 600, $timeout_prompt = '');

    public function getArgs($mode, $prompt_msg);

    public function getNextArg($prompt_msg = '');

    public function getFullArg($prompt_msg = '');

    public function setCache($key, $value);

    public function getCache($key);

    public function cloneFromParent();

    public function getNumArg($prompt_msg = '');

    public function copy();

    public function getOption();
}
