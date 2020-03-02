<?php


namespace ZM\Event\Swoole;


use Closure;
use Framework\ZMBuf;
use Swoole\Http\Request;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Annotation\Swoole\SwooleEventAt;
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
     * @inheritDoc
     */
    public function onActivate() {
        ZMUtil::checkWait();
        foreach (ZMBuf::globals("http_header") as $k => $v) {
            $this->response->setHeader($k, $v);
        }
        $uri = $this->request->server["request_uri"];
        if ($uri != "/") {
            $uri = explode("/", $uri);
            $uri = array_diff($uri, ["..", "", "."]);
            $node = ZMBuf::$req_mapping_node;
            $params = [];
            while (true) {
                $r = array_shift($uri);
                if ($r === null) break;
                if (($node2 = $node->getRealRoute($r, $params)) !== null) {
                    $node = $node2;
                    continue;
                } else {
                    $this->responseStatus(404);
                    return $this;
                }
            }
            if ($node->getRule() === null || call_user_func($node->getRule(), $this->request) === true) { //判断规则是否存在，如果有规则则走一遍规则
                if (in_array(strtoupper($this->request->server["request_method"]), $node->getRequestMethod())) { //判断目标方法在不在里面
                    $c_name = $node->getClass();
                    /** @var ModBase $class */
                    $class = new $c_name(["request" => $this->request, "response" => $this->response, "params" => $params], ModHandleType::SWOOLE_REQUEST);
                    $r = call_user_func_array([$class, $node->getMethod()], [$params]);
                    if (is_string($r) && !$this->response->isEnd()) $this->response->end($r);
                    if ($class->block_continue) return $this;
                    if ($this->response->isEnd()) return $this;
                }
            }
        } else {
            if (($node = ZMBuf::$req_mapping_node)->getMethod() !== null) {
                if (in_array(strtoupper($this->request->server["request_method"]), $node->getRequestMethod())) { //判断目标方法在不在里面
                    $c_name = $node->getClass();
                    /** @var ModBase $class */
                    $class = new $c_name(["request" => $this->request, "response" => $this->response], ModHandleType::SWOOLE_REQUEST);
                    $r = call_user_func_array([$class, $node->getMethod()], []);
                    if (is_string($r) && !$this->response->isEnd()) $this->response->end($r);
                    if ($class->block_continue) return $this;
                    if ($this->response->isEnd()) return $this;
                }
            }
            foreach (ZMBuf::$events[SwooleEventAt::class] ?? [] as $v) {
                if (strtolower($v->type) == "request" && $this->parseSwooleRule($v)) {
                    $c = $v->class;
                    $class = new $c(["request" => $this->request, "response" => $this->response]);
                    $r = call_user_func_array([$class, $v->method], []);
                    if ($class->block_continue) break;
                }
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
        switch ($v->rule) {
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