# 调试

在日常开发中，调试是一个重要的环节。在这里，我们将介绍如何在框架中调试。

## 日志记录

框架提供了一个日志记录组件，可以用来记录应用程序的运行日志。日志记录组件的使用方法请参考 [日志记录](/components/common/logging)。

## 打印变量

框架集成了 [VarDumper](https://symfony.com/doc/current/components/var_dumper.html) 组件，可以用来打印变量的值。

```php
dump($var);
```

## 调试工具

根据运行环境的不同（Swoole、Workerman 等），你可以使用不同的调试工具。

例如，你可以使用 [Xdebug](https://xdebug.org/) 或 [yasd](https://github.com/swoole/yasd) 等。这类工具的使用方式可参照对应文档。

或者，你也可以在代码中使用 psysh 下断点并查看变量。但在使用 psysh 时要注意，在多进程模式（多 Worker、含用户进程、TaskWorker 等模式）下，会出现未定义的行为，最好在单进程模式下使用。

```php
$x = 123;
// ... 你的代码
eval(\Psy\sh()); // 下断点位置
// ... 你的代码
$y = 456;
```

## 热更新

框架提供了热更新功能，可以在不重启应用程序的情况下更新代码，方便调试。热更新功能的使用方法请参考 [热更新](/components/common/hot-update)。
