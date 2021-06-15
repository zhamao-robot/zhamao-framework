<?php


namespace ZM\Event\SwooleEvent;


use Error;
use Exception;
use Swoole\Coroutine;
use Swoole\Http\Request;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Swoole\OnRequestEvent;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Context\Context;
use ZM\Event\EventDispatcher;
use ZM\Event\SwooleEvent;
use ZM\Exception\InterruptException;
use ZM\Http\Response;
use ZM\Utils\HttpUtil;

/**
 * Class OnRequest
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("request")
 */
class OnRequest implements SwooleEvent
{
    public function onCall(?Request $request, ?\Swoole\Http\Response $response) {
        $response = new Response($response);
        foreach (ZMConfig::get("global")["http_header"] as $k => $v) {
            $response->setHeader($k, $v);
        }
        unset(Context::$context[Coroutine::getCid()]);
        Console::debug("Calling Swoole \"request\" event from fd=" . $request->fd);
        set_coroutine_params(["request" => $request, "response" => $response]);

        $dis1 = new EventDispatcher(OnRequestEvent::class);
        $dis1->setRuleFunction(function ($v) {
            /** @noinspection PhpUnreachableStatementInspection */
            return eval("return " . $v->getRule() . ";") ? true : false;
        });

        $dis = new EventDispatcher(OnSwooleEvent::class);
        $dis->setRuleFunction(function ($v) {
            if ($v->getRule() == '') {
                return strtolower($v->type) == 'request';
            } else {
                /** @noinspection PhpUnreachableStatementInspection */
                if (strtolower($v->type) == 'request' && eval("return " . $v->getRule() . ";")) return true;
                else return false;
            }
        });

        try {
            $dis1->dispatchEvents($request, $response);
            $dis->dispatchEvents($request, $response);
            if ($dis->status === EventDispatcher::STATUS_NORMAL && $dis1->status === EventDispatcher::STATUS_NORMAL) {
                $result = HttpUtil::parseUri($request, $response, $request->server["request_uri"], $node, $params);
                if ($result === true) {
                    ctx()->setCache("params", $params);
                    $dispatcher = new EventDispatcher(RequestMapping::class);
                    $div = new RequestMapping();
                    $div->route = $node["route"];
                    $div->params = $params;
                    $div->method = $node["method"];
                    $div->request_method = $node["request_method"];
                    $div->class = $node["class"];
                    //Console::success("正在执行路由：".$node["method"]);
                    $dispatcher->dispatchEvent($div, null, $params, $request, $response);
                    if (is_string($dispatcher->store) && !$response->isEnd()) $response->end($dispatcher->store);
                }
            }
            if (!$response->isEnd()) {
                //Console::warning('返回了404');
                HttpUtil::responseCodePage($response, 404);
            }
        } catch (InterruptException $e) {
            // do nothing
        } catch (Exception $e) {
            $response->status(500);
            Console::info($request->server["remote_addr"] . ":" . $request->server["remote_port"] .
                " [" . $response->getStatusCode() . "] " . $request->server["request_uri"]
            );
            if (!$response->isEnd()) {
                if (ZMConfig::get("global", "debug_mode"))
                    $response->end(zm_internal_errcode("E00023") . "Internal server exception: " . $e->getMessage());
                else
                    $response->end(zm_internal_errcode("E00023") . "Internal server error.");
            }
            Console::error(zm_internal_errcode("E00023") . "Internal server exception (500), caused by " . get_class($e) . ": " . $e->getMessage());
            Console::log($e->getTraceAsString(), "gray");
        } catch (Error $e) {
            $response->status(500);
            Console::info($request->server["remote_addr"] . ":" . $request->server["remote_port"] .
                " [" . $response->getStatusCode() . "] " . $request->server["request_uri"]
            );
            if (!$response->isEnd()) {
                $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                if (ZMConfig::get("global", "debug_mode"))
                    $response->end(zm_internal_errcode("E00023") . "Internal server error: " . $error_msg);
                else
                    $response->end(zm_internal_errcode("E00023") . "Internal server error.");
            }
            Console::error(zm_internal_errcode("E00023") . "Internal server error (500), caused by " . get_class($e) . ": " . $e->getMessage());
            Console::log($e->getTraceAsString(), "gray");
        }
    }
}