# ZMUtil 杂项工具类

调用前先 use：`use ZM\Utils\ZMUtil;`

## ZMUtil::stop()

停止框架运行。

## ZMUtil::reload()

重载框架，这会断开所有到框架的连接和重载所有 `src/` 目录下的用户源码并重新加载所有 Worker 进程。

## ZMUtil::getModInstance()

根据类名称拿到此类的单例（前提是目标的类的构造函数为空）。

```php
class ASD{
    public $test = 0;
}
ZMUtil::getModInstance(ASD::class)->test = 5;
```

## ZMUtil::getReloadableFiles()

返回可通过热重启（reload）来重新加载的 php 文件列表。

以下是示例模块下的例子（直接拉取最新的框架源码并运行框架后获取的）。

```php
array:31 [
  94 => "src/ZM/Context/Context.php"
  95 => "src/ZM/Context/ContextInterface.php"
  96 => "src/ZM/Annotation/AnnotationParser.php"
  97 => "src/Custom/Annotation/Example.php"
  98 => "src/ZM/Annotation/Interfaces/CustomAnnotation.php"
  99 => "src/Module/Example/Hello.php"
  100 => "src/ZM/Annotation/Swoole/OnStart.php"
  101 => "src/ZM/Annotation/CQ/CQCommand.php"
  102 => "src/ZM/Annotation/Interfaces/Level.php"
  103 => "src/ZM/Annotation/Command/TerminalCommand.php"
  104 => "src/ZM/Annotation/Http/RequestMapping.php"
  105 => "src/ZM/Annotation/Http/RequestMethod.php"
  106 => "src/ZM/Annotation/Http/Middleware.php"
  107 => "src/ZM/Annotation/Interfaces/ErgodicAnnotation.php"
  108 => "src/ZM/Annotation/Swoole/OnOpenEvent.php"
  109 => "src/ZM/Annotation/Swoole/OnSwooleEventBase.php"
  110 => "src/ZM/Annotation/Interfaces/Rule.php"
  111 => "src/ZM/Annotation/Swoole/OnCloseEvent.php"
  112 => "src/ZM/Annotation/Swoole/OnRequestEvent.php"
  113 => "src/ZM/Http/RouteManager.php"
  114 => "vendor/symfony/routing/RouteCollection.php"
  115 => "vendor/symfony/routing/Route.php"
  116 => "src/Module/Middleware/TimerMiddleware.php"
  117 => "src/ZM/Http/MiddlewareInterface.php"
  118 => "src/ZM/Annotation/Http/MiddlewareClass.php"
  119 => "src/ZM/Annotation/Http/HandleBefore.php"
  120 => "src/ZM/Annotation/Http/HandleAfter.php"
  121 => "src/ZM/Annotation/Http/HandleException.php"
  122 => "src/ZM/Event/EventManager.php"
  123 => "src/ZM/Annotation/Swoole/OnSwooleEvent.php"
  124 => "src/ZM/Event/EventDispatcher.php"
]
```

> 为什么不能重载所有文件？因为框架是多进程模型，而重载相当于只重新启动了一次 Worker 进程，Manager 和 Master 进程未重启，所以被 Manager、Master 进程已经加载的 PHP 文件无法使用 reload 命令重新加载。详见 [进阶 - 进程间隔离](/advanced/multi-process/#_5)。
