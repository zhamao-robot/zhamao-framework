<?php


namespace ZM\Context;


use Co;
use Exception;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use ZM\ConnectionManager\ConnectionObject;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Exception\InterruptException;
use ZM\Exception\InvalidArgumentException;
use ZM\Exception\WaitTimeoutException;
use ZM\Http\Response;
use ZM\API\ZMRobot;
use ZM\Utils\CoMessage;

class Context implements ContextInterface
{
    public static $context = [];
    private $cid;

    public function __construct($cid) { $this->cid = $cid; }

    /**
     * @return Server
     */
    public function getServer(): ?Server { return self::$context[$this->cid]["server"] ?? server(); }

    /**
     * @return Frame|null
     */
    public function getFrame(): ?Frame { return self::$context[$this->cid]["frame"] ?? null; }

    public function getFd(): ?int { return self::$context[$this->cid]["fd"] ?? $this->getFrame()->fd ?? null; }

    /**
     * @return mixed
     */
    public function getData() { return self::$context[$this->cid]["data"] ?? null; }

    public function setData($data) { self::$context[$this->cid]["data"] = $data; }

    /**
     * @return Request|null
     */
    public function getRequest(): ?Request { return self::$context[$this->cid]["request"] ?? null; }

    /**
     * @return Response|null
     */
    public function getResponse(): ?Response { return self::$context[$this->cid]["response"] ?? null; }

    /** @return ConnectionObject|null|Response */
    public function getConnection() { return ManagerGM::get($this->getFd()); }

    /**
     * @return int|null
     */
    public function getCid(): ?int { return $this->cid; }

    /**
     * @return ZMRobot|null
     */
    public function getRobot(): ?ZMRobot {
        $conn = ManagerGM::get($this->getFrame()->fd);
        return $conn instanceof ConnectionObject ? new ZMRobot($conn) : null;
    }

    public function getMessage() { return self::$context[$this->cid]["data"]["message"] ?? null; }

    public function setMessage($msg) { self::$context[$this->cid]["data"]["message"] = $msg; }

    public function getUserId() { return $this->getData()["user_id"] ?? null; }

    public function setUserId($id) { self::$context[$this->cid]["data"]["user_id"] = $id; }

    public function getGroupId() { return $this->getData()["group_id"] ?? null; }

    public function setGroupId($id) { self::$context[$this->cid]["data"]["group_id"] = $id; }

    public function getDiscussId() { return $this->getData()["discuss_id"] ?? null; }

    public function setDiscussId($id) { self::$context[$this->cid]["data"]["discuss_id"] = $id; }

    public function getMessageType(): ?string { return $this->getData()["message_type"] ?? null; }

    public function setMessageType($type) { self::$context[$this->cid]["data"]["message_type"] = $type; }

    public function getRobotId() { return $this->getData()["self_id"] ?? null; }

    public function getCache($key) { return self::$context[$this->cid]["cache"][$key] ?? null; }

    public function setCache($key, $value) { self::$context[$this->cid]["cache"][$key] = $value; }

    public function getCQResponse() { return self::$context[$this->cid]["cq_response"] ?? null; }

    /**
     * only can used by cq->message event function
     * @param $msg
     * @param bool $yield
     * @return mixed
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function reply($msg, $yield = false) {
        $data = $this->getData();
        $conn = $this->getConnection();
        if (!is_array($msg)) {
            switch ($this->getData()["message_type"]) {
                case "group":
                case "private":
                case "discuss":
                    $this->setCache("has_reply", true);
                    $operation["reply"] = $msg;
                    $operation["at_sender"] = false;
                    return (new ZMRobot($conn))->setCallback($yield)->callExtendedAPI(".handle_quick_operation", [
                        "context" => $data,
                        "operation" => $operation
                    ]);
            }
            return false;
        } else {
            $operation = $msg;
            return (new ZMRobot($conn))->setCallback(false)->callExtendedAPI(".handle_quick_operation", [
                "context" => $data,
                "operation" => $operation
            ]);
        }
    }

    /**
     * @param $msg
     * @param false $yield
     * @return mixed|void
     * @throws InterruptException
     */
    public function finalReply($msg, $yield = false) {
        self::$context[$this->cid]["cache"]["block_continue"] = true;
        if ($msg != "") $this->reply($msg, $yield);
        EventDispatcher::interrupt();
    }

    /**
     * @param string $prompt
     * @param int $timeout
     * @param string $timeout_prompt
     * @return string
     * @throws InvalidArgumentException
     * @throws WaitTimeoutException
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function waitMessage($prompt = "", $timeout = 600, $timeout_prompt = "") {
        if (!isset($this->getData()["user_id"], $this->getData()["message"], $this->getData()["self_id"]))
            throw new InvalidArgumentException("协程等待参数缺失");

        Console::debug("==== 开始等待输入 ====");
        if ($prompt != "") $this->reply($prompt);

        try {
            $r = CoMessage::yieldByWS($this->getData(), ["user_id", "self_id", "message_type", onebot_target_id_name($this->getMessageType())], $timeout);
        } catch (Exception $e) {
            $r = false;
        }
        if ($r === false) {
            throw new WaitTimeoutException($this, $timeout_prompt);
        }
        return $r["message"];
        /*
        $cid = Co::getuid();
        $api_id = ZMAtomic::get("wait_msg_id")->add(1);
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
        SpinLock::lock("wait_api");
        $hw = LightCacheInside::get("wait_api", "wait_api") ?? [];
        $hw[$api_id] = $hang;
        LightCacheInside::set("wait_api", "wait_api", $hw);
        SpinLock::unlock("wait_api");
        $id = swoole_timer_after($timeout * 1000, function () use ($api_id, $timeout_prompt) {
            $r = LightCacheInside::get("wait_api", "wait_api")[$api_id] ?? null;
            if (is_array($r)) {
                Co::resume($r["coroutine"]);
            }
        });

        Co::suspend();
        SpinLock::lock("wait_api");
        $hw = LightCacheInside::get("wait_api", "wait_api") ?? [];
        $sess = $hw[$api_id];
        unset($hw[$api_id]);
        LightCacheInside::set("wait_api", "wait_api", $hw);
        $result = $sess["result"];
        if (isset($id)) swoole_timer_clear($id);
        if ($result === null) throw new WaitTimeoutException($this, $timeout_prompt);
        return $result;*/
    }

    /**
     * @param $mode
     * @param $prompt_msg
     * @return mixed|string
     * @throws InvalidArgumentException
     * @throws WaitTimeoutException
     */
    public function getArgs($mode, $prompt_msg) {
        $arg = ctx()->getCache("match");
        switch ($mode) {
            case ZM_MATCH_ALL:
                $p = $arg;
                return trim(implode(" ", $p)) == "" ? $this->waitMessage($prompt_msg) : trim(implode(" ", $p));
            case ZM_MATCH_NUMBER:
                foreach ($arg as $k => $v) {
                    if (is_numeric($v)) {
                        array_splice($arg, $k, 1);
                        ctx()->setCache("match", $arg);
                        return $v;
                    }
                }
                return $this->waitMessage($prompt_msg);
            case ZM_MATCH_FIRST:
                if (isset($arg[0])) {
                    $a = $arg[0];
                    array_splice($arg, 0, 1);
                    ctx()->setCache("match", $arg);
                    return $a;
                } else {
                    return $this->waitMessage($prompt_msg);
                }
        }
        throw new InvalidArgumentException();
    }

    /**
     * @param string $prompt_msg
     * @return int|mixed|string
     * @throws InvalidArgumentException
     * @throws WaitTimeoutException
     */
    public function getNextArg($prompt_msg = "") { return $this->getArgs(ZM_MATCH_FIRST, $prompt_msg); }

    /**
     * @param string $prompt_msg
     * @return int|mixed|string
     * @throws InvalidArgumentException
     * @throws WaitTimeoutException
     */
    public function getFullArg($prompt_msg = "") { return $this->getArgs(ZM_MATCH_ALL, $prompt_msg); }

    /**
     * @param string $prompt_msg
     * @return int|mixed|string
     * @throws InvalidArgumentException
     * @throws WaitTimeoutException
     */
    public function getNumArg($prompt_msg = "") { return $this->getArgs(ZM_MATCH_NUMBER, $prompt_msg); }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function cloneFromParent() {
        set_coroutine_params(self::$context[Co::getPcid()] ?? self::$context[$this->cid]);
        return context();
    }

    public function copy() { return self::$context[$this->cid]; }

    public function getOption() { return self::getCache("match"); }
}
