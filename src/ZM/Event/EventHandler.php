<?php


namespace ZM\Event;


use Co;
use Doctrine\Common\Annotations\AnnotationException;
use Error;
use Exception;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ConnectionObject;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Event\Swoole\{MessageEvent, RequestEvent, WSCloseEvent, WSOpenEvent};
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\WebSocket\Frame;
use ZM\Annotation\CQ\CQAPIResponse;
use ZM\Annotation\CQ\CQAPISend;
use ZM\Annotation\Http\MiddlewareClass;
use ZM\Context\Context;
use ZM\Http\MiddlewareInterface;
use ZM\Http\Response;
use ZM\Store\ZMBuf;
use ZM\Utils\ZMUtil;

class EventHandler
{
    /**
     * @param $event_name
     * @param $param0
     * @param null $param1
     * @throws AnnotationException
     */
    public static function callSwooleEvent($event_name, $param0, $param1 = null) {
        //$starttime = microtime(true);
        unset(Context::$context[Co::getCid()]);
        $event_name = strtolower($event_name);
        switch ($event_name) {
            case "message":
                /** @var Frame $param1 */
                /** @var Server $param0 */
                $conn = ManagerGM::get($param1->fd);
                set_coroutine_params(["server" => $param0, "frame" => $param1, "connection" => $conn]);
                try {
                    (new MessageEvent($param0, $param1))->onActivate()->onAfter();
                } catch (Error $e) {
                    $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                    Console::error("Fatal error when calling $event_name: " . $error_msg);
                    Console::trace();
                }
                break;
            case "request":
                try {
                    set_coroutine_params(["request" => $param0, "response" => $param1]);
                    (new RequestEvent($param0, $param1))->onActivate()->onAfter();
                } catch (Exception $e) {
                    /** @var Response $param1 */
                    $param1->status(500);
                    Console::info($param0->server["remote_addr"] . ":" . $param0->server["remote_port"] .
                        " [" . $param1->getStatusCode() . "] " . $param0->server["request_uri"]
                    );
                    if (!$param1->isEnd()) {
                        if (ZMConfig::get("global", "debug_mode"))
                            $param1->end("Internal server error: " . $e->getMessage());
                        else
                            $param1->end("Internal server error.");
                    }
                    Console::error("Internal server exception (500), caused by " . get_class($e));
                    Console::log($e->getTraceAsString(), "gray");
                } catch (Error $e) {
                    /** @var Response $param1 */
                    $param1->status(500);
                    Console::info($param0->server["remote_addr"] . ":" . $param0->server["remote_port"] .
                        " [" . $param1->getStatusCode() . "] " . $param0->server["request_uri"]
                    );
                    $doc = "Internal server error<br>";
                    $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                    if (Console::getLevel() >= 4) $doc .= $error_msg;
                    if (!$param1->isEnd()) $param1->end($doc);
                    Console::error("Internal server error (500): " . $error_msg);
                    Console::log($e->getTraceAsString(), "gray");
                }
                break;
            case "open":
                /** @var Request $param1 */
                set_coroutine_params(["server" => $param0, "request" => $param1, "fd" => $param1->fd]);
                try {
                    (new WSOpenEvent($param0, $param1))->onActivate()->onAfter();
                } catch (Error $e) {
                    $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                    Console::error("Fatal error when calling $event_name: " . $error_msg);
                    Console::trace();
                }
                break;
            case "close":
                set_coroutine_params(["server" => $param0, "fd" => $param1]);
                try {
                    (new WSCloseEvent($param0, $param1))->onActivate()->onAfter();
                } catch (Error $e) {
                    $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                    Console::error("Fatal error when calling $event_name: " . $error_msg);
                    Console::trace();
                }
                break;
        }
        //Console::info(Console::setColor("Event: " . $event_name . " 运行了 " . round(microtime(true) - $starttime, 5) . " 秒", "gold"));
    }

    /**
     * @param $event_data
     * @param $conn_or_response
     * @param int $level
     * @return bool
     * @throws AnnotationException
     */
    public static function callCQEvent($event_data, $conn_or_response, int $level = 0) {
        ctx()->setCache("level", $level);
        if ($level >= 5) {
            Console::warning("Recursive call reached " . $level . " times");
            Console::trace();
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

    /**
     * @param $req
     * @throws AnnotationException
     */
    public static function callCQResponse($req) {
        Console::debug("收到来自API连接的回复：" . json_encode($req, 128 | 256));
        $status = $req["status"];
        $retcode = $req["retcode"];
        $data = $req["data"];
        if (isset($req["echo"]) && ZMBuf::array_key_exists("sent_api", $req["echo"])) {
            $origin = ZMBuf::get("sent_api")[$req["echo"]];
            $self_id = $origin["self_id"];
            $response = [
                "status" => $status,
                "retcode" => $retcode,
                "data" => $data,
                "self_id" => $self_id,
                "echo" => $req["echo"]
            ];
            set_coroutine_params(["cq_response" => $response]);
            if (isset(ZMBuf::$events[CQAPIResponse::class][$req["retcode"]])) {
                list($c, $method) = ZMBuf::$events[CQAPIResponse::class][$req["retcode"]];
                $class = new $c(["data" => $origin["data"]]);
                call_user_func_array([$class, $method], [$origin["data"], $req]);
            }
            $origin_ctx = ctx()->copy();
            ctx()->setCache("action", $origin["data"]["action"] ?? "unknown");
            ctx()->setData($origin["data"]);
            foreach (ZMBuf::$events[CQAPISend::class] ?? [] as $k => $v) {
                if (($v->action == "" || $v->action == ctx()->getCache("action")) && $v->with_result) {
                    $c = $v->class;
                    self::callWithMiddleware($c, $v->method, context()->copy(), [ctx()->getCache("action"), $origin["data"]["params"] ?? [], ctx()->getRobotId()]);
                    if (context()->getCache("block_continue") === true) break;
                }
            }
            set_coroutine_params($origin_ctx);
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

    public static function callCQAPISend($reply, ?ConnectionObject $connection) {
        $action = $reply["action"] ?? null;
        if ($action === null) {
            Console::warning("API 激活事件异常！");
            return;
        }
        if (ctx() === null) $content = [];
        else $content = ctx()->copy();
        go(function () use ($action, $reply, $connection, $content) {
            set_coroutine_params($content);
            context()->setCache("action", $action);
            context()->setCache("reply", $reply);
            foreach (ZMBuf::$events[CQAPISend::class] ?? [] as $k => $v) {
                if (($v->action == "" || $v->action == $action) && !$v->with_result) {
                    $c = $v->class;
                    self::callWithMiddleware($c, $v->method, context()->copy(), [$reply["action"], $reply["params"] ?? [], $connection->getOption('connect_id')]);
                    if (context()->getCache("block_continue") === true) break;
                }
            }
        });
    }

    /**
     * @param $c
     * @param $method
     * @param array $class_construct
     * @param array $func_args
     * @param null $after_call
     * @return mixed|null
     * @throws AnnotationException
     * @throws Exception
     */
    public static function callWithMiddleware($c, $method, array $class_construct, array $func_args, $after_call = null) {
        $return_value = null;
        $plain_class = is_object($c) ? get_class($c) : $c;
        if (isset(ZMBuf::$events[MiddlewareInterface::class][$plain_class][$method])) {
            $middlewares = ZMBuf::$events[MiddlewareInterface::class][$plain_class][$method];
            $before_result = true;
            $r = [];
            foreach ($middlewares as $k => $middleware) {
                if (!isset(ZMBuf::$events[MiddlewareClass::class][$middleware])) throw new AnnotationException("Annotation parse error: Unknown MiddlewareClass named \"{$middleware}\"!");
                $middleware_obj = ZMBuf::$events[MiddlewareClass::class][$middleware];
                $before = $middleware_obj["class"];
                //var_dump($middleware_obj);
                $r[$k] = new $before();
                $r[$k]->class = is_object($c) ? get_class($c) : $c;
                $r[$k]->method = $method;
                if (isset($middleware_obj["before"])) {
                    $rs = $middleware_obj["before"];
                    $before_result = $r[$k]->$rs(...$func_args);
                    if ($before_result === false) break;
                }
            }
            if ($before_result) {
                try {
                    if (is_object($c)) $class = $c;
                    elseif ($class_construct == []) $class = ZMUtil::getModInstance($c);
                    else $class = new $c($class_construct);
                    $result = $class->$method(...$func_args);
                    if (is_callable($after_call))
                        $return_value = $after_call($result);
                } catch (Exception $e) {
                    for ($i = count($middlewares) - 1; $i >= 0; --$i) {
                        $middleware_obj = ZMBuf::$events[MiddlewareClass::class][$middlewares[$i]];
                        if (!isset($middleware_obj["exceptions"])) continue;
                        foreach ($middleware_obj["exceptions"] as $name => $method) {
                            if ($e instanceof $name) {
                                $r[$i]->$method($e);
                                context()->setCache("block_continue", true);
                            }
                        }
                        if (context()->getCache("block_continue") === true) return $return_value;
                    }
                    throw $e;
                }
            }
            for ($i = count($middlewares) - 1; $i >= 0; --$i) {
                $middleware_obj = ZMBuf::$events[MiddlewareClass::class][$middlewares[$i]];
                if (isset($middleware_obj["after"], $r[$i])) {
                    $r[$i]->{$middleware_obj["after"]}(...$func_args);
                }
            }
        } else {
            if (is_object($c)) $class = $c;
            elseif ($class_construct == []) $class = ZMUtil::getModInstance($c);
            else $class = new $c($class_construct);
            $result = call_user_func_array([$class, $method], $func_args);
            if (is_callable($after_call))
                $return_value = call_user_func_array($after_call, [$result]);
        }
        return $return_value;
    }
}
