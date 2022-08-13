<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Http\HttpFactory;
use OneBot\Http\Stream;
use OneBot\Util\Singleton;
use Stringable;
use Throwable;
use ZM\Annotation\AnnotationHandler;
use ZM\Annotation\Framework\BindEvent;
use ZM\Annotation\Http\Route;
use ZM\Container\ContainerServicesProvider;
use ZM\Exception\ConfigException;
use ZM\Utils\HttpUtil;

class HttpEventListener
{
    use Singleton;

    /**
     * 框架自身要实现的 HttpRequestEvent 事件回调
     * 这里处理框架特有的内容，比如：
     * 路由、断点续传、注解再分发等
     *
     * @throws Throwable
     */
    public function onRequest999(HttpRequestEvent $event)
    {
        // 注册容器
        resolve(ContainerServicesProvider::class)->registerServices('request', $event);
        // 跑一遍 BindEvent 绑定了 HttpRequestEvent 的注解
        $handler = new AnnotationHandler(BindEvent::class);
        $handler->setRuleCallback(function (BindEvent $anno) {
            return $anno->event_class === HttpRequestEvent::class;
        });
        $handler->handleAll($event);
        // dump($event->getResponse());
        $node = null;
        $params = null;
        // 如果状态是 Normal，那么说明跑了一遍没有阻塞或者其他的情况，我就直接跑一遍内部的路由分发和静态文件分发
        if ($handler->getStatus() === AnnotationHandler::STATUS_NORMAL && $event->getResponse() === null) {
            // 解析路由和路由状态
            $result = HttpUtil::parseUri($event->getRequest(), $node, $params);
            switch ($result) {
                case ZM_ERR_NONE:   // 解析到存在路由了
                    $handler = new AnnotationHandler(Route::class);
                    $div = new Route($node['route']);
                    $div->params = $params;
                    $div->method = $node['method'];
                    $div->request_method = $node['request_method'];
                    $div->class = $node['class'];
                    $starttime = microtime(true);
                    $handler->handle($div, null, $params, $event->getRequest(), $event);
                    if (is_string($val = $handler->getReturnVal()) || ($val instanceof Stringable)) {
                        $event->withResponse(HttpFactory::getInstance()->createResponse(200, null, [], Stream::create($val)));
                    } elseif ($event->getResponse() === null) {
                        $event->withResponse(HttpFactory::getInstance()->createResponse(500));
                    }
                    logger()->warning('Used ' . round((microtime(true) - $starttime) * 1000, 3) . ' ms');
                    break;
                case ZM_ERR_ROUTE_METHOD_NOT_ALLOWED:
                    $event->withResponse(HttpUtil::handleHttpCodePage(405));
                    break;
            }
        }
    }

    /**
     * 遍历结束所有的如果还是没有响应，那么就找静态文件路由
     *
     * @throws ConfigException
     */
    public function onRequest1(HttpRequestEvent $event)
    {
        if ($event->getResponse() === null) {
            $response = HttpUtil::handleStaticPage($event->getRequest()->getUri()->getPath());
            $event->withResponse($response);
        }
        container()->flush();
    }
}
