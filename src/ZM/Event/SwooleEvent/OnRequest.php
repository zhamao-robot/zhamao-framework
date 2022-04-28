<?php

declare(strict_types=1);

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
use ZM\Container\Container;
use ZM\Context\Context;
use ZM\Context\ContextInterface;
use ZM\Event\EventDispatcher;
use ZM\Event\SwooleEvent;
use ZM\Exception\InterruptException;
use ZM\Http\Response;
use ZM\Utils\HttpUtil;

/**
 * Class OnRequest
 * @SwooleHandler("request")
 */
class OnRequest implements SwooleEvent
{
    public function onCall(?Request $request, ?\Swoole\Http\Response $response)
    {
        $response = new Response($response);
        foreach (ZMConfig::get('global')['http_header'] as $k => $v) {
            $response->setHeader($k, $v);
        }
        unset(Context::$context[Coroutine::getCid()]);
        Console::debug('Calling Swoole "request" event from fd=' . $request->fd);
        set_coroutine_params(['request' => $request, 'response' => $response]);

        $this->registerRequestContainerBindings($request, $response);

        $dis1 = new EventDispatcher(OnRequestEvent::class);
        $dis1->setRuleFunction(function ($v) {
            return (bool) eval('return ' . $v->getRule() . ';');
        });

        $dis = new EventDispatcher(OnSwooleEvent::class);
        $dis->setRuleFunction(function ($v) {
            if ($v->getRule() == '') {
                return strtolower($v->type) == 'request';
            }
            if (strtolower($v->type) == 'request' && eval('return ' . $v->getRule() . ';')) {
                return true;
            }
            return false;
        });

        try {
            $dis1->dispatchEvents($request, $response);
            $dis->dispatchEvents($request, $response);
            if ($dis->status === EventDispatcher::STATUS_NORMAL && $dis1->status === EventDispatcher::STATUS_NORMAL) {
                $result = HttpUtil::parseUri($request, $response, $request->server['request_uri'], $node, $params);
                if ($result === true) {
                    ctx()->setCache('params', $params);
                    $dispatcher = new EventDispatcher(RequestMapping::class);
                    $div = new RequestMapping($node['route']);
                    $div->params = $params;
                    $div->method = $node['method'];
                    $div->request_method = $node['request_method'];
                    $div->class = $node['class'];
                    $dispatcher->dispatchEvent($div, null, $params, $request, $response);

                    $this->response($response, $dispatcher->store);
                }
            }
            if (!$response->isEnd()) {
                HttpUtil::responseCodePage($response, 404);
            }
        } catch (InterruptException $e) {
            // do nothing
        } catch (Exception $e) {
            $response->status(500);
            Console::info(
                $request->server['remote_addr'] . ':' . $request->server['remote_port'] .
                ' [' . $response->getStatusCode() . '] ' . $request->server['request_uri']
            );
            if (!$response->isEnd()) {
                if (ZMConfig::get('global', 'debug_mode')) {
                    $response->end(zm_internal_errcode('E00023') . 'Internal server exception: ' . $e->getMessage());
                } else {
                    $response->end(zm_internal_errcode('E00023') . 'Internal server error.');
                }
            }
            Console::error(zm_internal_errcode('E00023') . 'Internal server exception (500), caused by ' . get_class($e) . ': ' . $e->getMessage());
            Console::log($e->getTraceAsString(), 'gray');
        } catch (Error $e) {
            $response->status(500);
            Console::info(
                $request->server['remote_addr'] . ':' . $request->server['remote_port'] .
                ' [' . $response->getStatusCode() . '] ' . $request->server['request_uri']
            );
            if (!$response->isEnd()) {
                $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
                if (ZMConfig::get('global', 'debug_mode')) {
                    $response->end(zm_internal_errcode('E00023') . 'Internal server error: ' . $error_msg);
                } else {
                    $response->end(zm_internal_errcode('E00023') . 'Internal server error.');
                }
            }
            Console::error(zm_internal_errcode('E00023') . 'Internal server error (500), caused by ' . get_class($e) . ': ' . $e->getMessage());
            Console::log($e->getTraceAsString(), 'gray');
        } finally {
            container()->flush();
        }
    }

    /**
     * 注册请求容器绑定
     */
    private function registerRequestContainerBindings(Request $request, Response $response): void
    {
        $container = Container::getInstance();
//        $container->setLogPrefix("[Container#{$frame->fd}]");
        $container->instance(Request::class, $request);
        $container->bind(ContextInterface::class, function () {
            return ctx();
        });
        $container->alias(ContextInterface::class, Context::class);
    }

    /**
     * 返回响应
     * @param mixed $result
     */
    private function response(Response $response, $result): void
    {
        if (is_string($result)) {
            $response->end($result);
        } else {
            try {
                $response->header('Content-Type', 'application/json');
                $response->end(json_encode($result, JSON_UNESCAPED_UNICODE));
            } catch (Exception $e) {
                Console::error('无法将响应转换为JSON：' . $e->getMessage());
                $response->end(json_encode(zm_internal_errcode('E00023') . 'Internal server error.'));
            }
        }
    }
}
