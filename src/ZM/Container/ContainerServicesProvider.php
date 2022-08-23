<?php

declare(strict_types=1);

namespace ZM\Container;

use Closure;
use OneBot\Driver\Driver;
use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Driver\Process\ProcessManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ZM\Context\Context;
use ZM\Context\ContextInterface;
use ZM\Framework;

class ContainerServicesProvider
{
    /**
     * 注册服务
     *
     * ```
     * 作用域：
     * global: worker start
     * request: request
     * message: message
     * connection: open, close, message
     * ```
     *
     * @param string $scope 作用域
     */
    public function registerServices(string $scope, ...$params): void
    {
        switch ($scope) {
            case 'global':
                $this->registerGlobalServices(WorkerContainer::getInstance());
                break;
            case 'request':
                $this->registerRequestServices(Container::getInstance(), ...$params);
                break;
            case 'message':
                $this->registerConnectionServices(Container::getInstance());
                $this->registerMessageServices(Container::getInstance());
                break;
            case 'connection':
                $this->registerConnectionServices(Container::getInstance());
                break;
            default:
                break;
        }
    }

    /**
     * 清理服务
     */
    public function cleanup(): void
    {
        container()->flush();
    }

    /**
     * 注册全局服务
     */
    private function registerGlobalServices(ContainerInterface $container): void
    {
        // 注册路径类的容器快捷方式
        $container->instance('path.working', WORKING_DIR);
        $container->instance('path.source', SOURCE_ROOT_DIR);
        $container->alias('path.source', 'path.base');
        $container->instance('path.data', config('global.data_dir'));
        $container->instance('path.framework', FRAMEWORK_ROOT_DIR);

        // 注册worker和驱动
        $container->instance('worker_id', ProcessManager::getProcessId());
        $container->instance(Driver::class, Framework::getInstance()->getDriver());

        // 注册logger
        $container->instance(LoggerInterface::class, logger());
    }

    /**
     * 注册请求服务（HTTP请求）
     */
    private function registerRequestServices(ContainerInterface $container, HttpRequestEvent $event): void
    {
        // $context = Context::$context[zm_cid()];
        $container->instance(HttpRequestEvent::class, $event);
        $container->alias(HttpRequestEvent::class, 'http.request.event');
        $container->instance(ServerRequestInterface::class, $event->getRequest());
        $container->alias(ServerRequestInterface::class, 'http.request');
        // $container->instance(Request::class, $context['request']);
        // $container->instance(Response::class, $context['response']);
        $container->bind(ContextInterface::class, Context::class);
        // $container->alias(ContextInterface::class, Context::class);
    }

    /**
     * 注册消息服务（WS消息）
     */
    private function registerMessageServices(ContainerInterface $container): void
    {
        // $context = Context::$context[zm_cid()];
        // $container->instance(Frame::class, $context['frame']); // WS 消息帧
        // $container->bind(ContextInterface::class, Closure::fromCallable('ctx'));
        // $container->alias(ContextInterface::class, Context::class);
    }

    /**
     * 注册链接服务
     */
    private function registerConnectionServices(ContainerInterface $container): void
    {
        // $context = Context::$context[zm_cid()];
        // $container->instance(ConnectionObject::class, $context['connection']);
    }
}
