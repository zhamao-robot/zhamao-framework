# 更新日志（master 分支 commit）

此文档将显示非发布版的提交版本相关更新文档，可能与发布版的更新日志有重合，在此仅作更新记录。

同时此处将只使用 build 版本号进行区分。

## build 477 (2022-8-5)

- 修复了 `@CQNotice`、`@CQRequest` 注解无法正常激活的 Bug (#140)

## build 476 (2022-8-3)

- `DataProvider::scanDirFiles()` 新增参数 `$include_dir`，用于控制非递归模式下是否包含目录

## build 475 (2022-7-3)

- 修复 `match_args` 全局方法对于 `0` 字符串处理的 Bug（#136）

## build 474 (2022-5-21)

- 修复 WebSocket 连接时报错的 Bug

## build 473 (2022-5-7)

- 修复 `server:stop` 命令下部分情况报错的问题

## build 472 (2022-5-6)

- 修复 Container 环境继承全局变量的问题

## build 471 (2022-5-5)

- 修复 `CQ::encode()` 无法传入 `int` 的强类型解析问题（#113）
- （内部）重构 CQ 类
- 新增启动命令参数 `--audit-mode`，用于单次审计模式
- 修复 `EventMapIterator` 对 PHP 8.1 的兼容性问题
- 修复 #95 中提到的无输入流时报错的问题
- 新增部分不可执行脚本的防呆退出功能
- 修复 `ZMServer` 中的 typo

## build 470 (2022-5-4)

- 重构帮助生成器，将帮助生成器重构为 `CommandInfoUtil` 类

## build 469 (2022-5-3)

- 新增 `@CommandArgument` 注解，可直接通过注解添加聊天机器人命令参数
- 修改默认 Hello 模块下随机数功能为采用 `@CommandArgument` 注解模式
- （内部）新增 `EventManager::$event_map`，用于补充对事件对象遍历的方式
- 新增 `EventMapIterator` 类，用于遍历注解事件对象
- 新增 `MessageUtil::checkArguments()` 方法，用于检查 `@CommandArgument` 注解

## build 468 (2022-4-30)

- 优化单元测试流程
- 优化上下文对象，在非协程环境下不再会抛出异常或返回 null

## build 467 (2022-4-29)

- 优化 `@RequestMapping` 注解事件的方法返回值处理，支持数组和字符串（数组自动转为 JSON 格式）

## build 466 (2022-4-29)

- 优化容器支持无名顺序参数的调用
- 优化静态路由，支持 64 以上长度的路由

## build 464 (2022-4-28)

- 重构全局方法 `match_pattern()`，优化性能以及解决部分字符串不能匹配的 Bug

## build 463 (2022-4-16)

- 新增链式调用全局方法 `chain()`
- 新增函数执行时间工具全局函数 `stopwatch()`

## build 462 (2022-4-15)

- 新增依赖注入、容器支持，目前对 Swoole 事件、机器人事件均支持使用依赖注入
- 新增全局容器方法 `container()`、`resolve()`、`app()`，用于获取容器参数等
- 新增相关容器测试

## build 461 (2022-4-11)

- 新增 AllBotsProxy、AllGroupsProxy 代理类，支持批量发送机器人动作
- 新增全局函数 `implode_when_necessary()`，用于将可能为数组的参数转换为字符串

## build 460 (2022-4-3)

- 优化代码到 phpstan-level-4

## build 459 (2022-4-3)

- 优化代码到 phpstan-level-2

## build 458 (2022-4-3)

- 优化代码到 phpstan-level-1
- 优化 `module:xxx` 类命令的有关实现代码

## build 457 (2022-4-2)

- 新增和优化测试用例（具体见文件）
- 部分文件以 PHPStan Level 2 进行规范化

## build 456 (2022-3-30)

- 重构 phpunit-swoole，使其可以正常使用
- 新增 `--private-mode` 参数，用于隐藏启动前的 MOTD 及敏感信息
- 修复 Composer extra 配置项 `zm.exclude-annotation-path` 不能正常工作的 Bug
- 优化注解事件加载器，防止 Master 进程中添加的事件在 Worker 中被覆盖的问题
- 修复 `DataProvider::isRelativePath()` 方法判断有误的 Bug
- 新增退出框架时支持以非 0 exit code 退出的功能
- 优化 `ZMUtil::getClassesPsr4()` 方法，排除不含类的文件
- 优化 PHP CS Fixer 的配置
- 新增测试用例（具体见文件）

## build 455 (2022-3-27)

- 修复前几个小版本无法收发消息的 Bug
- 新增 API Document 自动生成脚本

## build 454 (2022-3-27)

- 修复部分命令下无法杀掉进程的 Bug
- 新增 `@Cron` 注解
- 修复全局函数 `match_pattern` 无法正常工作的 Bug

## build 453 (2022-3-25)

- 新增 property 注解用于 IDE 识别

## build 452 (2022-3-25)

- 修复 OnSetup 注解无法使用 Attribute 解析的 Bug
- 修复 HelpGenerator 的 Alias 不工作的 Bug

## build 451 (2022-3-21)

- 重构全局函数，统一函数命名，并补全注释

## build 450 (2022-3-21)

- 新增命令帮助生成器

## build 449 (2022-3-21)

- 新增 Composer 模块加载和分发模式

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
