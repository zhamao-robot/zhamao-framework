<?php


namespace ZM\Event\Swoole;


use Closure;
use Exception;
use Framework\Console;
use Framework\ZMBuf;
use Swoole\Http\Request;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Event\EventHandler;
use ZM\Http\Response;
use Framework\DataProvider;
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

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return $this|SwooleEvent
     * @throws Exception
     */
    public function onActivate()
    {
        ZMUtil::checkWait();
        foreach (ZMBuf::globals("http_header") as $k => $v) {
            $this->response->setHeader($k, $v);
        }
        $uri = $this->request->server["request_uri"];
        Console::verbose($this->request->server["remote_addr"] . " request " . $uri);
        $uri = explode("/", $uri);
        $uri = array_diff($uri, ["..", "", "."]);
        $node = ZMBuf::$req_mapping;
        $params = [];
        while (true) {
            $r = array_shift($uri);
            if ($r === null) {
                if ($node == ZMBuf::$req_mapping) goto statics;
                else break;
            }
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
            statics:
            if (ZMBuf::globals("static_file_server")["status"]) {
                $base_dir = ZMBuf::globals("static_file_server")["document_root"];
                $base_index = ZMBuf::globals("static_file_server")["document_index"];
                $uri = $this->request->server["request_uri"];
                $path = realpath($base_dir . urldecode($uri));
                if ($path !== false) {
                    if (is_dir($path) && mb_substr($uri, -1, 1) != "/") {
                        $this->response->redirect($uri . "/", 301);
                        $this->response->end();
                        return $this;
                    }
                    if (is_dir($path)) $path = $path . '/';
                    $work = realpath(DataProvider::getWorkingDir()) . '/';
                    if (strpos($path, $work) !== 0) {
                        $this->responseStatus(403);
                        return $this;
                    }
                    if (is_dir($path)) {
                        foreach ($base_index as $vp) {
                            if (is_file($path . $vp)) {
                                Console::info("[200] " . $uri . " (static)");
                                $exp = strtolower(pathinfo($path . $vp)['extension'] ?? "unknown");
                                $this->response->setHeader("Content-Type", ZMBuf::config("file_header")[$exp] ?? "application/octet-stream");
                                $this->response->end(file_get_contents($path . $vp));
                                return $this;
                            }
                        }
                    } elseif (is_file($path)) {
                        Console::info("[200] " . $uri . " (static)");
                        $exp = strtolower(pathinfo($path)['extension'] ?? "unknown");
                        $this->response->setHeader("Content-Type", ZMBuf::config("file_header")[$exp] ?? "application/octet-stream");
                        $this->response->end(file_get_contents($path));
                        return $this;
                    }
                }
            }
            $this->response->status(404);
            $this->response->end(ZMUtil::getHttpCodePage(404));
            return $this;
        }

        context()->setCache("params", $params);

        if (in_array(strtoupper($this->request->server["request_method"]), $node["request_method"] ?? [])) { //判断目标方法在不在里面
            $c_name = $node["class"];
            EventHandler::callWithMiddleware(
                $c_name,
                $node["method"],
                ["request" => $this->request, "response" => &$this->response, "params" => $params],
                [$params],
                function ($result) {
                    if (is_string($result) && !$this->response->isEnd()) $this->response->end($result);
                    if ($this->response->isEnd()) context()->setCache("block_continue", true);
                }
            );
        }
        foreach (ZMBuf::$events[SwooleEventAt::class] ?? [] as $v) {
            if (strtolower($v->type) == "request" && $this->parseSwooleRule($v)) {
                $c = $v->class;
                EventHandler::callWithMiddleware($c, $v->method, ["request" => $this->request, "response" => $this->response], []);
                if (context()->getCache("block_continue") === true) break;
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
    public function onAfter()
    {
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

    private function responseStatus(int $int)
    {
        $this->response->status($int);
        $this->response->end();
    }

    private function parseSwooleRule($v)
    {
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
