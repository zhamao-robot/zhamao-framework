<?php


namespace ZM\API;

use Co;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Console\Console;
use ZM\Event\EventHandler;
use ZM\Store\ZMBuf;

trait CQAPI
{
    /**
     * @param ConnectionObject $connection
     * @param $reply
     * @param |null $function
     * @return bool|array
     */
    private function processAPI($connection, $reply, $function = null) {
        if ($connection->getOption("type") === CONN_WEBSOCKET)
            return $this->processWebsocketAPI($connection, $reply, $function);
        else
            return $this->processHttpAPI($connection, $reply, $function);


    }

    public function processWebsocketAPI($connection, $reply, $function = null) {
        $api_id = ZMBuf::atomic("wait_msg_id")->get();
        $reply["echo"] = $api_id;
        ZMBuf::atomic("wait_msg_id")->add(1);
        EventHandler::callCQAPISend($reply, $connection);
        if (is_callable($function)) {
            ZMBuf::appendKey("sent_api", $api_id, [
                "data" => $reply,
                "time" => microtime(true),
                "func" => $function,
                "self_id" => $connection->getOption("connect_id")
            ]);
        } elseif ($function === true) {
            ZMBuf::appendKey("sent_api", $api_id, [
                "data" => $reply,
                "time" => microtime(true),
                "coroutine" => Co::getuid(),
                "self_id" => $connection->getOption("connect_id")
            ]);
        } else {
            ZMBuf::appendKey("sent_api", $api_id, [
                "data" => $reply,
                "time" => microtime(true),
                "self_id" => $connection->getOption("connect_id")
            ]);
        }

        if (server()->push($connection->getFd(), json_encode($reply))) {
            if ($function === true) {
                Co::suspend();
                $data = ZMBuf::get("sent_api")[$api_id];
                ZMBuf::unsetByValue("sent_api", $reply["echo"]);
                return isset($data['result']) ? $data['result'] : null;
            }
            return true;
        } else {
            Console::warning("CQAPI send failed, websocket push error.");
            $response = [
                "status" => "failed",
                "retcode" => -1000,
                "data" => null,
                "self_id" => $connection->getOption("connect_id")
            ];
            $s = ZMBuf::get("sent_api")[$reply["echo"]];
            if (($s["func"] ?? null) !== null)
                call_user_func($s["func"], $response, $reply);
            ZMBuf::unsetByValue("sent_api", $reply["echo"]);
            if ($function === true) return $response;
            return false;
        }
    }

    public function processHttpAPI($connection, $reply, $function = null) {
        return false;
    }

    public function __call($name, $arguments) {
        return false;
    }
}
