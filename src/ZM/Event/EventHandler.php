<?php


namespace ZM\Event;


use Co;
use Error;
use Exception;
use Framework\Console;
use Framework\ZMBuf;
use ZM\Event\Swoole\{MessageEvent, RequestEvent, WorkerStartEvent, WSCloseEvent, WSOpenEvent};
use ZM\Http\Response;
use ZM\Utils\DataProvider;
use ZM\Utils\ZMUtil;

class EventHandler
{
    public static function callSwooleEvent($event_name, $param0, $param1 = null) {
        //$starttime = microtime(true);
        $event_name = strtolower($event_name);
        switch ($event_name) {
            case "workerstart":
                try {
                    register_shutdown_function(function () {
                        $error = error_get_last();
                        if ($error["type"] != 0) {
                            Console::error("Internal fatal error: " . $error["message"] . " at " . $error["file"] . "({$error["line"]})");
                        }
                        DataProvider::saveBuffer();
                        ZMBuf::$server->shutdown();
                    });
                    (new WorkerStartEvent($param0, $param1))->onActivate()->onAfter();
                    Console::log("\n=== Worker #" . $param0->worker_id . " 已启动 ===\n", "gold");
                } catch (Exception $e) {
                    Console::error("Worker加载出错！停止服务！");
                    Console::error($e->getMessage() . "\n" . $e->getTraceAsString());
                    ZMUtil::stop();
                    return;
                } catch (Error $e) {
                    var_export($e);
                    ZMUtil::stop();
                }
                break;
            case "message":
                (new MessageEvent($param0, $param1))->onActivate()->onAfter();
                break;
            case "request":
                try {
                    (new RequestEvent($param0, $param1))->onActivate()->onAfter();
                } catch (Exception $e) {
                    /** @var Response $param1 */
                    $param1->status(500);
                    Console::info($param0->server["remote_addr"] . ":" . $param0->server["remote_port"] .
                        " [" . $param1->getStatusCode() . "] " . $param0->server["request_uri"]
                    );
                    if (!$param1->isEnd()) $param1->end("Internal server error: " . $e->getMessage());
                    Console::error("Internal server error (500), caused by uncaught exception.");
                    Console::log($e->getTraceAsString(), "gray");
                }
                break;
            case "open":
                (new WSOpenEvent($param0, $param1))->onActivate()->onAfter();
                break;
            case "close":
                (new WSCloseEvent($param0, $param1))->onActivate()->onAfter();
                break;
        }
        //Console::info(Console::setColor("Event: " . $event_name . " 运行了 " . round(microtime(true) - $starttime, 5) . " 秒", "gold"));
    }

    public static function callCQEvent($event_data, $conn_or_response, int $level = 0) {
        if ($level >= 5) {
            Console::warning("Recursive call reached " . $level . " times");
            Console::stackTrace();
            return false;
        }
        $starttime = microtime(true);
        switch ($event_data["post_type"]) {
            case "message":
                $event = new CQ\MessageEvent($event_data, $conn_or_response, $level);
                if ($event->onBefore()) $event->onActivate();
                $event->onAfter();
                return $event->hasReply();
                break;
            case "notice":
                $event = new CQ\NoticeEvent($event_data, $conn_or_response, $level);
                if ($event->onBefore()) $event->onActivate();
                $event->onAfter();
                return true;
            case "request":
                $event = new CQ\RequestEvent($event_data, $conn_or_response, $level);
                if ($event->onBefore()) $event->onActivate();
                $event->onAfter();
                return true;
            case "meta_event":
                $event = new CQ\MetaEvent($event_data, $conn_or_response, $level);
                if ($event->onBefore()) $event->onActivate();
                return true;
        }
        unset($starttime);
        return false;
    }

    public static function callCQResponse($req) {
        //Console::info("收到来自API连接的回复：".json_encode($req, 128|256));
        if (isset($req["echo"]) && ZMBuf::array_key_exists("sent_api", $req["echo"])) {
            $status = $req["status"];
            $retcode = $req["retcode"];
            $data = $req["data"];
            $origin = ZMBuf::get("sent_api")[$req["echo"]];
            $self_id = $origin["self_id"];
            $response = [
                "status" => $status,
                "retcode" => $retcode,
                "data" => $data,
                "self_id" => $self_id
            ];
            if (($origin["func"] ?? null) !== null) {
                call_user_func($origin["func"], $response, $origin["data"]);
            } elseif (($origin["coroutine"] ?? false) !== false) {
                $p = ZMBuf::get("sent_api");
                $p[$req["echo"]]["result"] = $response;
                ZMBuf::set("sent_api", $p);
                Co::resume($origin['coroutine']);
            }
            ZMBuf::unsetByValue("sent_api", $req["echo"]);
        }
    }
}
