# 日志

框架支持使用支持 [PSR-3](https://github.com/php-fig/log) 的日志组件。

框架默认使用 `zhamao/logger` 组件作为日志组件，但是用户可以根据自身的需求选择其他的日志组件。

## 自定义日志组件

更换日志组件的方式非常方便，只需要在 `Setup` 事件中调用 `ob_logger_register` 函数即可。

```php
#[\Setup]
public function setup()
{
    ob_logger_register(new \Your\Logger());
}
```

## 获取日志组件

框架提供了 `logger` 函数来获取日志组件。

```php
logger()->info('Hello World');
```

或者，你也可以通过依赖注入的方式来获取日志组件。

```php
public function __construct(\Psr\Log\LoggerInterface $logger)
{
    $logger->info('Hello World');
}
```

## 日志级别

日志组件支持 PSR-3 规范中定义的 8 个日志级别。

目前，你可以通过传递 `log-level` 命令行参数来设置日志级别。

```bash
./zhamao server --log-level=debug
```

但请注意这仅适用内置的 `zhamao/logger` 组件，如果你使用了其他的日志组件，你需要自行实现日志级别的设置。
