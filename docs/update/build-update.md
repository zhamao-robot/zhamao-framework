# 更新日志（master 分支 commit）

此文档将显示非发布版的提交版本相关更新文档，可能与发布版的更新日志有重合，在此仅作更新记录。

同时此处将只使用 build 版本号进行区分。

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
