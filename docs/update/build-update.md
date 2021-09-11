# 更新日志（master 分支 commit）

此文档将显示非发布版的提交版本相关更新文档，可能与发布版的更新日志有重合，在此仅作更新记录。

同时此处将只使用 build 版本号进行区分。

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
