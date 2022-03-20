# 更新日志（master 分支 commit）

此文档将显示非发布版的提交版本相关更新文档，可能与发布版的更新日志有重合，在此仅作更新记录。

同时此处将只使用 build 版本号进行区分。

## build 448 (2022-3-20)

- 加快 build 命令的执行速度，取消进度条和提升性能

## build 447 (2022-3-20)

- 发布 2.7.0 正式版

## build 446 (2022-3-20)

- 新增 `./zhamao server` 下的 `--no-state-check` 参数，关闭“启动框架前的运行状态检查”功能

## build 445 (2022-3-20)

- 新增配置项 `runtime`.`annotation_reader_ignore`：支持注解解析器忽略注解的自定义

## build 444 (2022-3-20)

- 更改 `extra`.`exclude_annotate` 为 `zm`.`exclude-annotation-path` 项

## build 443 (2022-3-20)

- 修复注释空格的样式

## build 442 (2022-3-20)

- 修复打包模块后 `files` 的 autoload 项不能被解压和引入的 Bug

## build 441 (2022-3-20)

- 修复打包模块时命名空间与实际不一致的 Bug

## build 440 (2022-3-20)

- 新增方法宏（Macroable）

## build 439 (2022-3-19)

- 新增 PHP 8 Attribute 与注解同时支持的特性

## build 438 (2022-3-18)

- 修复 Response 类在 PHP 8.1 环境下的报错

## build 437 (2022-3-17)

- 修复 `ctx()` 可能会返回 null 的 Bug

## build 436 (2022-3-15)

- 新增 PHPStan 和 PHP CS Fixer 并优化全局代码

## build 435 (2022-3-13)

- 优化分离 WorkerManager 与 ProcessManager 的职责
- 新增 Ctrl+C 一次无法停止框架时多次 Ctrl+C 后可强行杀掉所有进程的功能
- `./zhamao server:stop` 新增参数 `--force`，使用 `SIGKILL` 强行杀掉所有进程
- 新增 AnnotationParser 对 `autoload-dev` 项中的 `psr-4` 默认检索条件
- 新增框架启动状态检测功能，如果已经启动了同样目录的框架，则会报错
- 新增“强制启用轮询模式启动热更新”功能（参数 `--polling-watch`）
- 修复与 PHP 8.1 的兼容性
- 对 DaemonCommand 进行优化，与 ServerCommand 效果相同
- 修复 `autoload`.`psr-4` 不存在时报错的 Bug
- 新增框架停止时 Worker 退出回显状态码
- 新增 inotify 判断模式，如果使用 `--watch` 检测到没有安装 inotify，则自动使用轮询模式

## build 434 (2022-1-8)

- 修复框架在 PHP 8.1 下运行时的一些问题
- 新增 Console 日志输出到文件的功能

## build 433 (2021-12-28)

- 修复 OneBotV11 因 IDE 自动优化导致 API 接口发生变化的问题

## build 432 (2021-12-25)

- 新增 GoCqhttpAPI 包，用于支持额外的 OneBot Action（API）
- 修复 MySQL 查询器中 `fetchOne()` 方法无法正确返回值的 Bug
- 修复 Swoole Hook 因配置不当无法正确使用的 Bug

## build 431 (2021-12-22)

- 修复 Issue #50
- 新增 PhpStorm IDE 直接运行框架的脚本

## build 430 (2021-12-8)

- 删除调试信息
- 修复 #56 中关于数据库组件的 Bug

## build 429 (2021-12-7)

- 新增配置项 `onebot`.`message_command_policy`
- 新增 CQCommand 阻断策略的自定义配置功能
- 修复 CQAfter 无法正常使用的 bug #53

## build 428 (2021-11-16)

- 修复 `ctx()->waitMessage()` 在 array 消息模式下无法正确返回消息字符串的问题
- 新增 `ctx()->getArrayMessage()` 和 `ctx()->getStringMessage()` 两个方法
- 修复注解事件 `CQCommand` 和 `CQMessage` 在 array 消息模式下无法正确解析的 Bug

## build 427 (2021-11-16)

- 新增全局中间件，可在全局配置文件中设置
- 修复部分 Typo
- 新增指令 `server:status`、`server:reload`、`server:stop` 可用在新开终端中查看框架运行状态、重启和退出
- 新增支持 `array` 格式的消息
- 上下文 Context 对象新增 `getOriginMessage()` 用于获取原消息，`getMessage()` 如果在设置了转换后，将默认转换消息为字符串格式保持与旧模块兼容
- OneBot API 新增全局过滤器，可用作 Action 过滤重写等操作
- 配置文件新增 `runtime.reload_delay_time`，用于可配置重载 Worker 等待的时间（毫秒）
- 配置文件新增 `runtime.global_middleware_binding`，用于配置全局中间件
- 配置文件新增 `onebot.message_convert_string`，用于配置是否转换数组格式为字符串，保证与前版本的兼容性（默认为 true）
- MessageUtil 消息工具类新增方法：`strToArray($msg, bool $ignore_space = true, bool $trim_text = false)`
- MessageUtil 消息工具类新增方法：`arrayToStr(array $array)`
- 新增框架启动多次监测功能，无法使用同一个框架项目同时启动两个框架

## build 426 (2021-11-10)

- 修复 CQ 码的解析函数 Bug（#52）

## build 425 (2021-11-3)

- 删除未实际应用功能的配置参数
- 修复 reload 时会断开 WebSocket 连接且导致进程崩溃的 Bug

## build 424 (2021-11-2)

- 新增 InstantModule 类、ZMServer 类、ModuleBase 类
- 配置文件新增 `runtime.reload_kill_connect`、`runtime.global_middleware_binding` 选项
- 修复部分情况下闭包事件分发时崩溃的 bug
- 新增内部方法 `_zm_env_check`
- 调整默认的 OneBot 模块对应的等级从 99999 调整为 99
- 新增导出框架运行参数的列表功能

## build 423 (2021-10-17)

- 修复 PHP 7.2 ~ 7.3 下无法使用新版 MySQL 组件的 bug

## build 422 (2021-10-6)

- 修复 `script_` 前缀无法被排除加载模块的 bug
- 修复 MySQL 组件的依赖问题

## build 421 (2021-9-11)

- 删除多余的调试信息

## build 420 (2021-9-11)

- 修复 OneBot 事件无法响应的 bug
- 新增部分 EventDispatcher 触发的事件 debug 日志

## build 419 (2021-9-11)

- 修复 DB 模块在未连接数据库的时候抛出未知异常
- 修复部分情况下打包模块出现的错误

## build 418 (2021-9-10)

- 修复 ZMAtomic 在 test 环境下的 bug
- 修复 MessageUtil 的报错

## build 417 (2021-8-29)

- 新增 AnnotationException，统一框架内部的抛出异常的类型
- 新增 AnnotationParser 下的 `verifyMiddlewares()` 方法
- 私有化 CQAPI 类下的内部方法
- 将 WebSocket API 响应超时时间从 60 秒缩短为 30 秒
- 修复 DB 类不能使用旧查询器的 bug
- 统一 DB 类下抛出 Exception 的类型为 ZMException 的子类
- EventDispatcher 新增对 `middleware_error_policy` 的处理段
- 配置文件下 `runtime` 新增 `middleware_error_policy` 字段
- 将 LightCache 组件抛出的异常改为 LightCacheException
- ModuleManager 修复改配置的 `load_path` 不生效的 bug
- 修复打包时生成的 Phar Autoload 列表出错的 bug
- 将配置的 override 改为 overwrite
- 新增解包时忽略依赖的选项（`--ignore-depends`）
- 删除众多调试日志，修改部分调试日志为 debug 级别的输出
- 修改 `ZM\MySQL\MySQLManager` 下的 `getConnection()` 为 `getWrapper()`
- MySQLPool 对象新增 `getCount()` 方法
- 新增 MySQLQueryBuilder 类（`doctrine/dbal` 的 wrapper 类）
- 修复 MySQLStatement 封装原 dbal 组件时与连接池不兼容的 bug
- 新增 MySQLStatementWrapper 类
- 完善 MySQLWrapper 类，用作主要的查询对象控制类
- 编写外部插件加载方式（Phar 热加载功能）
- 修复 `ZMUtil::getClassesPsr4()` 方法在遇到空扩展名文件时的报错
