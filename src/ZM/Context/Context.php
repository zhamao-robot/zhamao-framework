<?php


namespace ZM\Context;


use Co;
use Framework\ZMBuf;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use swoole_server;
use ZM\API\CQAPI;
use ZM\Connection\ConnectionManager;
use ZM\Connection\CQConnection;
use ZM\Connection\WSConnection;
use ZM\Exception\InvalidArgumentException;
use ZM\Exception\WaitTimeoutException;
use ZM\Http\Response;
use ZM\Utils\ZMRobot;

class Context implements ContextInterface
{
    private $cid;

    public function __construct($cid) { $this->cid = $cid; }

    /**
     * @return swoole_server|null
     */
    public function getServer() { return ZMBuf::$context[$this->cid]["server"] ?? null; }

    /**
     * @return Frame|null
     */
    public function getFrame() { return ZMBuf::$context[$this->cid]["frame"] ?? null; }

    public function getFd() { return ZMBuf::$context[$this->cid]["fd"] ?? $this->getFrame()->fd ?? null; }

    /**
     * @return array|null
     */
    public function getData() { return ZMBuf::$context[$this->cid]["data"] ?? null; }

    public function setData($data) { ZMBuf::$context[$this->cid]["data"] = $data; }

    /**
     * @return Request|null
     */
    public function getRequest() { return ZMBuf::$context[$this->cid]["request"] ?? null; }

    /**
     * @return Response|null
     */
    public function getResponse() { return ZMBuf::$context[$this->cid]["response"] ?? null; }

    /** @return WSConnection */
    public function getConnection() { return ConnectionManager::get($this->getFrame()->fd); }

    /**
     * @return int|null
     */
    public function getCid() { return $this->cid; }

    /**
     * @return ZMRobot|null
     */
    public function getRobot() {
        $conn = ConnectionManager::get($this->getFrame()->fd);
        return $conn instanceof CQConnection ? new ZMRobot($conn) : null;
    }

    public function getMessage() { return ZMBuf::$context[$this->cid]["data"]["message"] ?? null; }

    public function setMessage($msg) { ZMBuf::$context[$this->cid]["data"]["message"] = $msg; }

    public function getUserId() { return $this->getData()["user_id"] ?? null; }

    public function setUserId($id) { ZMBuf::$context[$this->cid]["data"]["user_id"] = $id; }

    public function getGroupId() { return $this->getData()["group_id"] ?? null; }

    public function setGroupId($id) { ZMBuf::$context[$this->cid]["data"]["group_id"] = $id; }

    public function getDiscussId() { return $this->getData()["discuss_id"] ?? null; }

    public function setDiscussId($id) { ZMBuf::$context[$this->cid]["data"]["discuss_id"] = $id; }

    public function getMessageType() { return $this->getData()["message_type"] ?? null; }

    public function setMessageType($type) { ZMBuf::$context[$this->cid]["data"]["message_type"] = $type; }

    public function getRobotId() { return $this->getData()["self_id"] ?? null; }

    public function getCache($key) { return ZMBuf::$context[$this->cid]["cache"][$key] ?? null; }

    public function setCache($key, $value) { ZMBuf::$context[$this->cid]["cache"][$key] = $value; }

    /**
     * only can used by cq->message event function
     * @param $msg
     * @param bool $yield
     * @return mixed
     */
    public function reply($msg, $yield = false) {
        switch ($this->getData()["message_type"]) {
            case "group":
            case "private":
            case "discuss":
                return CQAPI::quick_reply(ConnectionManager::get($this->getFrame()->fd), $this->getData(), $msg, $yield);
        }
        return false;
    }

    public function finalReply($msg, $yield = false) {
        ZMBuf::$context[$this->cid]["cache"]["block_continue"] = true;
        if ($msg == "") return true;
        return $this->reply($msg, $yield);
    }

    /**
     * @param string $prompt
     * @param int $timeout
     * @param string $timeout_prompt
     * @return string
     * @throws InvalidArgumentException
     * @throws WaitTimeoutException
     */
    public function waitMessage($prompt = "", $timeout = 600, $timeout_prompt = "") {
        if ($prompt != "") $this->reply($prompt);
        if (!isset($this->getData()["user_id"], $this->getData()["message"], $this->getData()["self_id"]))
            throw new InvalidArgumentException("协程等待参数缺失");
        $cid = Co::getuid();
        $api_id = ZMBuf::$atomics["wait_msg_id"]->get();
        ZMBuf::$atomics["wait_msg_id"]->add(1);
        $hang = [
            "coroutine" => $cid,
            "user_id" => $this->getData()["user_id"],
            "message" => $this->getData()["message"],
            "self_id" => $this->getData()["self_id"],
            "message_type" => $this->getData()["message_type"],
            "result" => null
        ];
        if ($hang["message_type"] == "group" || $hang["message_type"] == "discuss") {
            $hang[$hang["message_type"] . "_id"] = $this->getData()[$this->getData()["message_type"] . "_id"];
        }
        ZMBuf::appendKey("wait_api", $api_id, $hang);
        $id = swoole_timer_after($timeout * 1000, function () use ($api_id, $timeout_prompt) {
            $r = ZMBuf::get("wait_api")[$api_id] ?? null;
            if ($r !== null) {
                Co::resume($r["coroutine"]);
            }
        });

        Co::suspend();
        $sess = ZMBuf::get("wait_api")[$api_id];
        ZMBuf::unsetByValue("wait_api", $api_id);
        $result = $sess["result"];
        if (isset($id)) swoole_timer_clear($id);
        if ($result === null) throw new WaitTimeoutException($this, $timeout_prompt);
        return $result;
    }

    /**
     * @param $arg
     * @param $mode
     * @param $prompt_msg
     * @return mixed|string
     * @throws InvalidArgumentException
     * @throws WaitTimeoutException
     */
    public function getArgs(&$arg, $mode, $prompt_msg) {
        switch ($mode) {
            case ZM_MATCH_ALL:
                $p = $arg;
                array_shift($p);
                return trim(implode(" ", $p)) == "" ? $this->waitMessage($prompt_msg) : trim(implode(" ", $p));
            case ZM_MATCH_NUMBER:
                foreach ($arg as $k => $v) {
                    if (is_numeric($v)) {
                        array_splice($arg, $k, 1);
                        return $v;
                    }
                }
                return $this->waitMessage($prompt_msg);
            case ZM_MATCH_FIRST:
                if (isset($arg[1])) {
                    $a = $arg[1];
                    array_splice($arg, 1, 1);
                    return $a;
                } else {
                    return $this->waitMessage($prompt_msg);
                }
        }
        throw new InvalidArgumentException();
    }

    public function cloneFromParent() {
        set_coroutine_params(ZMBuf::$context[Co::getPcid()] ?? ZMBuf::$context[$this->cid]);
        return context();
    }

    public function copy() { return ZMBuf::$context[$this->cid]; }
}
