<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use Choir\Http\HttpFactory;
use Choir\Http\Stream;
use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Util\Singleton;
use ZM\Annotation\AnnotationHandler;
use ZM\Annotation\Framework\BindEvent;
use ZM\Annotation\Http\Route;
use ZM\Container\ContainerRegistrant;
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
     * @throws \Throwable
     */
    public function onRequest999(HttpRequestEvent $event)
    {
        // 注册容器
        ContainerRegistrant::registerHttpRequestServices($event);
        // TODO: 这里有个bug，如果是用的Workerman+Fiber协程的话，有个前置协程挂起，这里获取到的Event是被挂起的Event对象，触发两次事件才能归正
        // 跑一遍 BindEvent 绑定了 HttpRequestEvent 的注解
        $handler = new AnnotationHandler(BindEvent::class);
        $handler->setRuleCallback(fn (BindEvent $anno) => $anno->event_class === HttpRequestEvent::class);
        $handler->handleAll($event);
        // dump($event->getResponse());
        $node = null;
        /** @var null|array $params */
        $params = null;
        // 如果状态是 Normal，那么说明跑了一遍没有阻塞或者其他的情况，我就直接跑一遍内部的路由分发和静态文件分发
        if ($handler->getStatus() === AnnotationHandler::STATUS_NORMAL && $event->getResponse() === null) {
            // 解析路由和路由状态
            $result = HttpUtil::parseUri($event->getRequest(), $node, $params);
            switch ($result) {
                case ZM_ERR_NONE:   // 解析到存在路由了
                    $route_handler = new AnnotationHandler(Route::class);
                    $div = new Route($node['route']);
                    $div->params = $params;
                    $div->method = $node['method'];
                    // TODO：这里有个bug，逻辑上 request_method 应该是个数组，而不是字符串，但是这里 $node['method'] 是字符串，所以这里只能用字符串来判断
                    // $div->request_method = $node['request_method'];
                    $div->class = $node['class'];
                    $route_handler->handle($div, null, $params, $event->getRequest(), $event);
                    if (is_string($val = $route_handler->getReturnVal()) || ($val instanceof \Stringable)) {
                        // 返回的内容是可以被字符串化的，就当作 Body 来返回，状态码 200
                        $event->withResponse(HttpFactory::createResponse(200, null, [], Stream::create($val)));
                    } elseif ($event->getResponse() === null) {
                        // 过了一遍 Route，没有促成 Response，则返回 500（路由必须有返回才行）
                        $event->withResponse(HttpFactory::createResponse(500));
                    }
                    break;
                case ZM_ERR_ROUTE_METHOD_NOT_ALLOWED:    // 路由检测到存在，但是方法不匹配，则返回 405，表示方法不受支持
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
    public function onRequest1(HttpRequestEvent $event): void
    {
        if ($event->getResponse() === null) {
            $response = HttpUtil::handleStaticPage($event->getRequest()->getUri()->getPath());
            $event->withResponse($response);
        }
    }
}
