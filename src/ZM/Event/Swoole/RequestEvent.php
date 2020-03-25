<?php


namespace ZM\Event\Swoole;


use Closure;
use Exception;
use Framework\ZMBuf;
use Swoole\Http\Request;
use ZM\Annotation\Http\MiddlewareClass;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Http\MiddlewareInterface;
use ZM\Http\Response;
use ZM\ModBase;
use ZM\ModHandleType;
use ZM\Utils\ZMUtil;

class RequestEvent implements SwooleEvent
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return $this|SwooleEvent
     * @throws Exception
     */
    public function onActivate() {
        ZMUtil::checkWait();
        foreach (ZMBuf::globals("http_header") as $k => $v) {
            $this->response->setHeader($k, $v);
        }
        $uri = $this->request->server["request_uri"];
        $uri = explode("/", $uri);
        $uri = array_diff($uri, ["..", "", "."]);
        $node = ZMBuf::$req_mapping;
        $params = [];
        while (true) {
            $r = array_shift($uri);
            if ($r === null) break;
            if (($cnt = count($node["son"] ?? [])) == 1) {
                if (isset($node["param_route"])) {
                    foreach ($node["son"] as $k => $v) {
                        if ($v["id"] == $node["param_route"]) {
                            $node = $v;
                            $params[mb_substr($v["name"], 1, -1)] = $r;
                            continue 2;
                        }
                    }
                } elseif ($node["son"][0]["name"] == $r) {
                    $node = $node["son"][0];
                    continue;
                } else {
                    $this->responseStatus(404);
                    return $this;
                }
            } elseif ($cnt >= 1) {
                if (isset($node["param_route"])) {
                    foreach ($node["son"] as $k => $v) {
                        if ($v["id"] == $node["param_route"]) {
                            $node = $v;
                            $params[mb_substr($v["name"], 1, -1)] = $r;
                            continue 2;
                        }
                    }
                }
                foreach ($node["son"] as $k => $v) {
                    if ($v["name"] == $r) {
                        $node = $v;
                        continue 2;
                    }
                }
            }
            $this->responseStatus(404);
            return $this;
        }

        if (in_array(strtoupper($this->request->server["request_method"]), $node["request_method"] ?? [])) { //判断目标方法在不在里面
            $c_name = $node["class"];
            if (isset(ZMBuf::$events[MiddlewareInterface::class][$c_name][$node["method"]])) {
                $middleware = ZMBuf::$events[MiddlewareInterface::class][$c_name][$node["method"]];
                $middleware = ZMBuf::$events[MiddlewareClass::class][$middleware];
                $before = $middleware["class"];
                $r = new $before();
                $before_result = true;
                if (isset($middleware["before"])) {
                    $before_result = call_user_func([$r, $middleware["before"]], $this->request, $this->response, $params);
                    if ($before_result !== false) $before_result = true;
                }
                if ($before_result) {
                    try {
                        /** @var ModBase $class */
                        $class = new $c_name(["request" => $this->request, "response" => $this->response, "params" => $params], ModHandleType::SWOOLE_REQUEST);
                        $result = call_user_func_array([$class, $node["method"]], [$params]);
                        if (is_string($result) && !$this->response->isEnd()) $this->response->end($result);
                        if (!$this->response->isEnd()) goto eventCall;
                    } catch (Exception $e) {
                        if (!isset($middleware["exception"])) throw $e;
                        if ($e instanceof $middleware["exception"]) {
                            call_user_func([$r, $middleware["exception_method"]], $e, $this->request, $this->response, $params);
                            return $this;
                        } else throw $e;
                    }
                }
                if (isset($middleware["after"])) {
                    call_user_func([$r, $middleware["after"]], $this->request, $this->response, $params);
                    return $this;
                }
            } else {
                /** @var ModBase $class */
                $class = new $c_name(["request" => $this->request, "response" => $this->response, "params" => $params], ModHandleType::SWOOLE_REQUEST);
                $r = call_user_func_array([$class, $node["method"]], [$params]);
                if (is_string($r) && !$this->response->isEnd()) $this->response->end($r);
                if (!$this->response->isEnd()) goto eventCall;
            }
        }
        eventCall:
        foreach (ZMBuf::$events[SwooleEventAt::class] ?? [] as $v) {
            if (strtolower($v->type) == "request" && $this->parseSwooleRule($v)) {
                $c = $v->class;
                $class = new $c(["request" => $this->request, "response" => $this->response]);
                $r = call_user_func_array([$class, $v->method], []);
                if ($class->block_continue) break;
            }
        }

        if (!$this->response->isEnd()) {
            $this->response->status(404);
            $this->response->end(ZMUtil::getHttpCodePage(404));
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function onAfter() {
        foreach (ZMBuf::$events[SwooleEventAfter::class] ?? [] as $v) {
            if (strtolower($v->type) == "request" && $this->parseSwooleRule($v)) {
                $c = $v->class;
                $class = new $c(["request" => $this->request, "response" => $this->response]);
                call_user_func_array([$class, $v->method], []);
                if ($class->block_continue) break;
            }
        }
        return $this;
    }

    private function responseStatus(int $int) {
        $this->response->status($int);
        $this->response->end();
    }

    private function parseSwooleRule($v) {
        switch (explode(":", $v->rule)[0]) {
            case "containsGet":
            case "containsPost":
                if ($v->callback instanceof Closure) return call_user_func($v->callback, $this->request);
                break;
            case "containsJson":
                $content = $this->request->rawContent();
                $content = json_decode($content, true);
                if ($content === null) return false;
                if ($v->callback instanceof Closure) return call_user_func($v->callback, $content);
                break;
        }
        return true;
    }
}