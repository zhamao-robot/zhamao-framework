<?php

declare(strict_types=1);

namespace ZM\Plugin\Traits;

use ZM\Annotation\Http\Route;

trait RouteTrait
{
    /** @var array 注册的路由列表 */
    protected array $routes = [];

    /**
     * 添加一个 HTTP 路由
     *
     * @param Route $route Route 注解对象
     */
    public function addHttpRoute(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * @internal
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
