# HTTP 路由管理

HTTP 路由管理器用作管理炸毛框架内 `@RequestMapping` 和静态目录的路由操作的，可在运行过程中编写添加路由。

类定义：`\ZM\Http\RouteManager`

> 2.3.0 版本起可用。

::: warning 注意

因为炸毛框架的路由实现是不基于跨进程的共享内存的，所以每次使用这里面的工具函数都需要单独在所有 Worker 进程中执行一次，最好的办法就是在启动框架时执行（`@OnStart(-1)` 即可，代表此注解事件将在每个工作进程中都被执行一次）。

:::

## 方法

### importRouteByAnnotation()

通过注解类导入路由。（注：此方法一般为框架内部使用）

定义：`importRouteByAnnotation(RequestMapping $vss, $method, $class, $methods_annotations)`

参数 `$vss`：RequestMapping 注解类，类中定义 `route` 和 `request_method` 即可。

参数 `$method, $class`：执行的目标注解事件函数位置，比如 `$class = \Module\Example\Hello::class`，`$method = 'hitokoto'`。

参数 `$methods_annotations`：需要绑定的 Controller 注解类数组，一般数组内建议只带有一个 Controller，如 `[$controller]`。

### addStaticFileRoute()

添加一个单目录（此目录下无子目录，只有文件）并绑定为一个路由。

定义：`addStaticFileRoute($route, $path)`

参数 `$route`：绑定的目标路由，如 `/images/`。

参数 `$path`：绑定的文件目录位置，如 `/root/zhamao-framework-starter/zm_data/images/`。

```php
/**
 * @OnStart(-1)
 */
public function onStart() {
    RouteManager::addStaticFileRoute("/images/", DataProvider::getDataFolder()."images/");
}
```

## 属性

### RouteManager::$routes

此为存放路由树的变量，请谨慎操作。

定义：`\Symfony\Component\Routing\RouteCollection | null`

炸毛框架使用了 Symfony 框架的 route 组件，有关详情请查阅 [文档](https://symfony.com/doc/current/routing.html)。
