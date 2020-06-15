<?php


namespace ZM;


use Co;
use Framework\ZMBuf;
use Swoole\Http\Request;
use ZM\API\CQAPI;
use ZM\Connection\WSConnection;
use ZM\Exception\InvalidArgumentException;
use ZM\Exception\WaitTimeoutException;
use ZM\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * Class ModBase
 * @package ZM
 * @deprecated
 */
abstract class ModBase
{
    /** @var Server */
    protected $server;
    /** @var Frame */
    protected $frame;
    /** @var array */
    protected $data;
    /** @var Request */
    protected $request;
    /** @var Response */
    protected $response;
    /** @var int */
    protected $fd;
    /** @var int */
    protected $worker_id;
    /** @var WSConnection */
    protected $connection;

    protected $handle_type = ModHandleType::CQ_MESSAGE;

    public $block_continue = false;

    public function __construct($param0 = [], $handle_type = 0) {
        if (isset($param0["server"])) $this->server = $param0["server"];
        if (isset($param0["frame"])) $this->frame = $param0["frame"];
        if (isset($param0["data"])) $this->data = $param0["data"];
        if (isset($param0["request"])) $this->request = $param0["request"];
        if (isset($param0["response"])) $this->response = $param0["response"];
        if (isset($param0["fd"])) $this->fd = $param0["fd"];
        if (isset($param0["worker_id"])) $this->worker_id = $param0["worker_id"];
        if (isset($param0["connection"])) $this->connection = $param0["connection"];
        $this->handle_type = $handle_type;
    }

    /**
     * only can used by cq->message event function
     * @param $msg
     * @param bool $yield
     * @return mixed
     */
    public function reply($msg, $yield = false) {
        switch ($this->data["message_type"]) {
            case "group":
            case "private":
            case "discuss":
                return CQAPI::quick_reply($this->connection, $this->data, $msg, $yield);
        }
        return false;
    }

    public function finalReply($msg, $yield = false) {
        $this->setBlock();
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
        if (!isset($this->data["user_id"], $this->data["message"], $this->data["self_id"]))
            throw new InvalidArgumentException("协程等待参数缺失");
        $cid = Co::getuid();
        $api_id = ZMBuf::$atomics["wait_msg_id"]->get();
        ZMBuf::$atomics["wait_msg_id"]->add(1);
        $hang = [
            "coroutine" => $cid,
            "user_id" => $this->data["user_id"],
            "message" => $this->data["message"],
            "self_id" => $this->data["self_id"],
            "message_type" => $this->data["message_type"],
            "result" => null
        ];
        if ($hang["message_type"] == "group" || $hang["message_type"] == "discuss") {
            $hang[$hang["message_type"] . "_id"] = $this->data[$this->data["message_type"] . "_id"];
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

    public function getMessage() { return $this->data["message"] ?? null; }

    public function getUserId() { return $this->data["user_id"] ?? null; }

    public function getGroupId() { return $this->data["group_id"] ?? null; }

    public function getMessageType() { return $this->data["message_type"] ?? null; }

    public function getRobotId() { return $this->data["self_id"]; }

    public function getConnection() { return $this->connection; }

    public function setBlock($result = true) { context()->setCache("block_continue", $result); }
}
