<?php


namespace ZMTest\Mock;


use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use ZM\API\ZMRobot;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Context\ContextInterface;
use ZM\Http\Response;

class Context implements ContextInterface
{

    /**
     * Context constructor.
     * @param $cid
     */
    public function __construct($cid) { }

    /**
     * @return Server
     */
    public function getServer() {
        // TODO: Implement getServer() method.
    }

    /**
     * @return Frame
     */
    public function getFrame() {
        // TODO: Implement getFrame() method.
    }

    /**
     * @return mixed
     */
    public function getData() {
        // TODO: Implement getData() method.
    }

    /**
     * @param $data
     * @return mixed
     */
    public function setData($data) {
        // TODO: Implement setData() method.
    }

    /**
     * @return ConnectionObject
     */
    public function getConnection() {
        // TODO: Implement getConnection() method.
    }

    /**
     * @return int|null
     */
    public function getFd() {
        // TODO: Implement getFd() method.
    }

    /**
     * @return int
     */
    public function getCid() {
        // TODO: Implement getCid() method.
    }

    /**
     * @return Response
     */
    public function getResponse() {
        // TODO: Implement getResponse() method.
    }

    /**
     * @return Request
     */
    public function getRequest() {
        // TODO: Implement getRequest() method.
    }

    /**
     * @return ZMRobot
     */
    public function getRobot() {
        // TODO: Implement getRobot() method.
    }

    /**
     * @return mixed
     */
    public function getUserId() {
        // TODO: Implement getUserId() method.
    }

    /**
     * @return mixed
     */
    public function getGroupId() {
        // TODO: Implement getGroupId() method.
    }

    /**
     * @return mixed
     */
    public function getDiscussId() {
        // TODO: Implement getDiscussId() method.
    }

    /**
     * @return string
     */
    public function getMessageType() {
        // TODO: Implement getMessageType() method.
    }

    /**
     * @return mixed
     */
    public function getRobotId() {
        // TODO: Implement getRobotId() method.
    }

    /**
     * @return mixed
     */
    public function getMessage() {
        // TODO: Implement getMessage() method.
    }

    /**
     * @param $msg
     * @return mixed
     */
    public function setMessage($msg) {
        // TODO: Implement setMessage() method.
    }

    /**
     * @param $id
     * @return mixed
     */
    public function setUserId($id) {
        // TODO: Implement setUserId() method.
    }

    /**
     * @param $id
     * @return mixed
     */
    public function setGroupId($id) {
        // TODO: Implement setGroupId() method.
    }

    /**
     * @param $id
     * @return mixed
     */
    public function setDiscussId($id) {
        // TODO: Implement setDiscussId() method.
    }

    /**
     * @param $type
     * @return mixed
     */
    public function setMessageType($type) {
        // TODO: Implement setMessageType() method.
    }

    /**
     * @return mixed
     */
    public function getCQResponse() {
        // TODO: Implement getCQResponse() method.
    }

    /**
     * @param $msg
     * @param bool $yield
     * @return mixed
     */
    public function reply($msg, $yield = false) {
        echo $msg.PHP_EOL;
        // TODO: Implement reply() method.
    }

    /**
     * @param $msg
     * @param bool $yield
     * @return mixed
     */
    public function finalReply($msg, $yield = false) {
        // TODO: Implement finalReply() method.
    }

    /**
     * @param string $prompt
     * @param int $timeout
     * @param string $timeout_prompt
     * @return mixed
     */
    public function waitMessage($prompt = "", $timeout = 600, $timeout_prompt = "") {
        // TODO: Implement waitMessage() method.
    }

    /**
     * @param $arg
     * @param $mode
     * @param $prompt_msg
     * @return mixed
     */
    public function getArgs(&$arg, $mode, $prompt_msg) {
        $r = $arg;
        array_shift($r);
        return array_shift($r);
        // TODO: Implement getArgs() method.
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setCache($key, $value) {
        // TODO: Implement setCache() method.
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getCache($key) {
        // TODO: Implement getCache() method.
    }

    /**
     * @return mixed
     */
    public function cloneFromParent() {
        // TODO: Implement cloneFromParent() method.
    }

    /**
     * @return mixed
     */
    public function copy() {
        // TODO: Implement copy() method.
    }
}
