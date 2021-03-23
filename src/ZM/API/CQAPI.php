<?php


namespace ZM\API;

use ZM\Console\Console;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Utils\CoMessage;

trait CQAPI
{
    /**
     * @param $connection
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

    public function processWebsocketAPI($connection, $reply, $function = false) {
        $api_id = ZMAtomic::get("wait_msg_id")->add(1);
        $reply["echo"] = $api_id;
        if (server()->push($connection->getFd(), json_encode($reply))) {
            if ($function === true) {
                $obj = [
                    "data" => $reply,
                    "time" => microtime(true),
                    "self_id" => $connection->getOption("connect_id"),
                    "echo" => $api_id
                ];
                return CoMessage::yieldByWS($obj, ["echo"], 60);
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
            SpinLock::lock("wait_api");
            $r = LightCacheInside::get("wait_api", "wait_api");
            unset($r[$reply["echo"]]);
            LightCacheInside::set("wait_api", "wait_api", $r);
            SpinLock::unlock("wait_api");
            if ($function === true) return $response;
            return false;
        }
    }

    /**
     * @param $connection
     * @param $reply
     * @param null $function
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function processHttpAPI($connection, $reply, $function = null): bool {
        return false;
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function __call($name, $arguments) {
        return false;
    }
}
