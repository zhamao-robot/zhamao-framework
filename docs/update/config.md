# 配置文件变更记录

这里将会记录各个主版本的框架升级后，涉及 `global.php` 的更新日志，你可以根据这里描述的内容与你的旧配置文件进行合并。

## v2.6.0 (build 427)

- 新增 `$config['runtime']` 下的 `reload_delay_time`、`global_middleware_binding` 项。
- 新增 `$config['onebot']` 下的 `message_convert_string` 项。

## v2.5.1 (build 417)

- 新增 `$config['runtime']` 下的 `middleware_error_policy` 选项。

## v2.5.0 (build 413)

- 新增 `$config['runtime']` 运行时设置。
- 删除 `$config['server_event_handler_class']`，默认在启动时全局扫描。
- 新增 `$config['module_loader']` 模块/插件 打包配置选项。
- 新增 `$config['mysql_config']`，取代原先的 `$config['sql_config']`，此外废弃原先的MySQL 查询器 `\ZM\DB\DB` 类。

更新部分：

```php
/** 一些框架与Swoole运行时设置的调整 */
$config['runtime'] = [
    'swoole_coroutine_hook_flags' => SWOOLE_HOOK_ALL & (~SWOOLE_HOOK_CURL),
    'swoole_server_mode' => SWOOLE_PROCESS
];

/** MySQL数据库连接信息，host留空则启动时不创建sql连接池 */
$config['mysql_config'] = [
    'host' => '',
    'port' => 3306,
    'unix_socket' => null,
    'username' => 'root',
    'password' => '123456',
    'dbname' => 'adb',
    'charset' => 'utf8mb4',
    'pool_size' => 64,
    'options' => [
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
];

/** 注册 Swoole Server 事件注解的类列表(deleted) */
// 删除
```

## v2.4.0 (build 400)
- 调整 `$config['modules']['onebot']` 配置项到 `$config['onebot']`，旧版本的此段会向下兼容，建议更新，
- 新增 `$config['remote_terminal']` 远程终端的配置项，新增此段即可。

更新部分：
```php
/** 机器人解析模块，关闭后无法使用如CQCommand等注解(上面的modules即将废弃) */
$config['onebot'] = [
    'status' => true,
    'single_bot_mode' => false,
    'message_level' => 99999
];

/** 一个远程简易终端，使用nc直接连接即可，但是不建议开放host为0.0.0.0(远程连接) */
$config['remote_terminal'] = [
    'status' => false,
    'host' => '127.0.0.1',
    'port' => 20002,
    'token' => ''
];
```