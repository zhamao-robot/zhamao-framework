<?php

declare(strict_types=1);

namespace ZM\Store;

use Symfony\Component\Routing\RouteCollection;

/**
 * 框架内部使用的全局变量
 */
class InternalGlobals
{
    /**
     * @var null|RouteCollection 用于保存 Route 注解的路由树
     * @internal
     */
    public static $routes;
}
