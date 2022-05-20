<?php

declare(strict_types=1);

namespace ZM\Container;

use Closure;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use ZM\Adapters\AdapterInterface;
use ZM\Adapters\OneBot11Adapter;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Context\Context;
use ZM\Context\ContextInterface;
use ZM\Framework;
use ZM\Utils\DataProvider;

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
     * connection: open, close
     * ```
     *
     * @param string $scope 作用域
     */
    public function registerServices(string $scope): void
    {
        switch ($scope) {
            case 'global':
                $this->registerGlobalServices(WorkerContainer::getInstance());
                break;
            case 'request':
                $this->registerRequestServices(Container::getInstance());
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
        $container->instance('path.working', DataProvider::getWorkingDir());
        $container->instance('path.source', DataProvider::getSourceRootDir());
        $container->alias('path.source', 'path.base');
        $container->instance('path.config', DataProvider::getSourceRootDir() . '/config');
        $container->instance('path.module_config', ZMConfig::get('global', 'config_dir'));
        $container->instance('path.data', DataProvider::getDataFolder());
        $container->instance('path.framework', DataProvider::getFrameworkRootDir());

        $container->instance('server', Framework::$server);
        $container->instance('worker_id', Framework::$server->worker_id);

        $container->singleton(AdapterInterface::class, OneBot11Adapter::class);
        $container->singleton(LoggerInterface::class, ZMConfig::get('logging.logger'));
    }

    /**
     * 注册请求服务（HTTP请求）
     */
    private function registerRequestServices(ContainerInterface $container): void
    {
        $context = Context::$context[zm_cid()];
        $container->instance(Request::class, $context['request']);
        $container->instance(Response::class, $context['response']);
        $container->bind(ContextInterface::class, Closure::fromCallable('ctx'));
        $container->alias(ContextInterface::class, Context::class);
    }

    /**
     * 注册消息服务（WS消息）
     */
    private function registerMessageServices(ContainerInterface $container): void
    {
        $context = Context::$context[zm_cid()];
        $container->instance(Frame::class, $context['frame']); // WS 消息帧
        $container->bind(ContextInterface::class, Closure::fromCallable('ctx'));
        $container->alias(ContextInterface::class, Context::class);
    }

    /**
     * 注册链接服务
     */
    private function registerConnectionServices(ContainerInterface $container): void
    {
        $context = Context::$context[zm_cid()];
        $container->instance(ConnectionObject::class, $context['connection']);
    }
}
