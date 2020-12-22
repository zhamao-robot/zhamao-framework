# 从炸毛框架 V1 升级

> 这里只写明可能在升级过程中会影响原先代码执行的部分，不包含新增的特性等。

### 需要改变命名空间的类

- `Framework\Console` -> `ZM\Console\Console`
- `Swlib\Util\SingletonTrait` -> `ZM\Utils\SingletonTrait`
- `ZM\Annotation\Http\Before` -> `ZM\Annotation\Http\HandleBefore`
- `ZM\Annotation\Http\After` -> `ZM\Annotation\Http\HandleAfter`
- `@SwooleEventAt` -> `@OnSwooleEvent`
- 删除 `@SwooleEventAfter`
- 删除 `ModBase`
- `@HandleEvent` -> `@SwooleHandler`
- `ZM\Utils\ZMRobot` -> `\ZM\API\ZMRobot`

### 方法名称变更

- `ZM\Console::stackTrace()` -> `ZM\Console::trace()`

### 注解的变化

`@OnSwooleEvent`（原 `@SwooleEventAt`）中，`rule` 参数不再是自定义语法的东西了（比如之前的 `connectType:qq` 之类的鸡肋语法），直接是可执行的 PHP 代码，比如 `3 == 4`，`connectIsQQ()` 之类的。

去除 `@CQAPISend`，因为目前没什么意义。

`@CQCommand` 中，`regexMatch` 变成 `pattern`，`fullMatch` 变成 `regex`，消除歧义（第一个是 * 号匹配符进行匹配的，第二个是标准的正则表达式匹配）。同时新增 `start_with`，`end_with`，`keyword` 平行选项。

`@OnTick` 注解新增第二个参数 `worker_id`，其中默认是 0，代表只在 `#0` 号工作进程上运行计时器。

### 中间件编写的改变

原先的 Middleware 是需要含有 `getName()` 方法才合法，现在不需要了，但是对 `@MiddlewareClass` 注解需要增加参数，也就是说原先 `getName()` 返回的名称现在需要写到 `@MiddlewareClass("xxx")` 这样的形式。

### ZMBuf 的变化

由于 2.0 框架使用了多进程模型，所以不能使用原先适用于单进程下全局变量的方式（ZMBuf）进行存取变量，所以 ZMBuf 下的所有方法都需要更改，其中 `get, set` 等对缓存操作的模型请根据 2.0 的文档变更使用 `Redis` 或内置的多进程共享内存可用的 `LightCache` 轻量缓存。

而获取全局配置文件，如 `global.php` 文件，也发生了变化，新框架引入了 `ZMConfig` 对象，可以快速地区分各类环境变量从而读取不同的配置文件。比如我们获取原先的 global 配置文件中的一项：`ZMBuf::globals("port")`，在 2.0 中需要使用 `ZMConfig::get("global", "port")` 方式。以此类推，`ZMBuf::config("xxx")` 也直接变为 `ZMConfig::get("xxx")` 了。