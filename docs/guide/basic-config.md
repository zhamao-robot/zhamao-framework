# 基本配置

到目前为止，炸毛框架的配置文件还没有任何变更，是默认的行为。在本章内容中，将列举出炸毛框架的配置文件的规则和使用。

!!! error "警告"

    因为炸毛框架的全局配置中含有数据库名称和密码以及 access_token 等敏感字段，在使用版本控制软件过程中请不要将敏感信息写入配置文件并提交至开源仓库！

## 全局配置文件 global.php

框架的全局配置文件在 `config/global.php` 文件中。下面是配置文件的各个选项，请根据自己的需要自行配置。

| 配置名称                     | 说明                                                         | 默认值                       |
| :--------------------------- | ------------------------------------------------------------ | ---------------------------- |
| `host`                       | 框架监听的地址                                               | 0.0.0.0                      |
| `port`                       | 框架监听的端口                                               | 20001                        |
| `http_reverse_link`          | 框架开到公网或外部的 HTTP 反代链接                           | 见配置文件                   |
| `zm_data`                    | 框架的配置文件、日志文件等文件目录                           | `./` 下的 `zm_data/`         |
| `debug_mode`                 | 框架是否启动 debug 模式                                      | false                        |
| `crash_dir`                  | 存放崩溃和运行日志的目录                                     | `zm_data` 下的 `crash/`      |
| `config_dir`                 | 存放 saveToJson() 方法保存的数据的目录                       | `zm_data` 下的 `config/`     |
| `swoole`                     | 对应 Swoole server 中 set 的参数，参考Swoole文档             | 见子表 `swoole`              |
| `runtime`                    | 一些框架运行时调整的设置                                     | 见子表 `runtime`             |
| `light_cache`                | 轻量内置 key-value 缓存                                      | 见字表 `light_cache`         |
| `worker_cache`               | 跨进程变量级缓存                                             | 见子表 `worker_cache`        |
| `mysql_config`               | MySQL 数据库连接信息                                         | 见子表 `mysql_config`        |
| `redis_config`               | Redis 连接信息                                               | 见子表 `redis_config`        |
| `access_token`               | OneBot 客户端连接约定的token，留空则无，相关设置见 [组件 - Access Token 验证](component/access-token) | 空                           |
| `http_header`                | HTTP 请求自定义返回的header                                  | 见配置文件                   |
| `http_default_code_page`     | HTTP服务器在指定状态码下回复的默认页面                       | 见配置文件                   |
| `init_atomics`               | 框架启动时初始化的原子计数器列表                             | 见配置文件                   |
| `info_level`                 | 终端日志显示等级（0-4）                                      | 2                            |
| `context_class`              | 上下文所定义的类，见对应上下文说明文档                       | `\ZM\Context\Context::class` |
| `static_file_server`         | 静态文件服务器配置项                                         | 见子表 `static_file_server`  |
| `server_event_handler_class` | 注册 Swoole Server 事件注解的类列表，在 Swoole 服务器启动前就被加载 | 空                           |
| `onebot`                     | OneBot 协议相关配置                                          | 见子表 `onebot`              |
| `remote_terminal`            | 远程终端相关配置                                             | 见子表 `remote_terminal`     |
| `module_loader`              | 模块/插件 加载相关配置                                       | 见子表 `module_loader` |

### 子表 **swoole**

| 配置名称                | 说明                                                         | 默认值                              |
| ----------------------- | ------------------------------------------------------------ | ----------------------------------- |
| `log_file`              | Swoole 的日志文件                                            | `crash_dir` 下的 `swoole_error.log` |
| `worker_num`            | Worker 工作进程数                                            | 运行框架的主机 CPU 核心数           |
| `dispatch_mode`         | 数据包分发策略，见 [文档](https://wiki.swoole.com/#/server/setting?id=dispatch_mode) | 2                                   |
| `max_coroutine`         | 最大协程并发数                                               | 300000                              |
| `max_wait_time`         | 退出进程时等待协程恢复的最长时间（秒）                       | 5（2.4.3 版本后默认值）             |
| `task_worker_num`       | TaskWorker 工作进程数                                        | 默认不开启（此参数被注释）          |
| `task_enable_coroutine` | TaskWorker 工作进程启用协程                                  | 默认不开启（此参数被注释）或 `bool` |

### 子表 runtime

| 配置名称                      | 说明                                                         | 默认值                                  |
| ----------------------------- | ------------------------------------------------------------ | --------------------------------------- |
| `swoole_coroutine_hook_flags` | Swoole 启动时一键协程化 Hook 的 Flag 值，详见 [一键协程化](http://wiki.swoole.com/#/runtime?id=%e5%87%bd%e6%95%b0%e5%8e%9f%e5%9e%8b) | `SWOOLE_HOOK_ALL & (~SWOOLE_HOOK_CURL)` |
| `swoole_server_mode`          | Swoole Server 启动的进程模式，有 `SWOOLE_PROCESS` 和 `SWOOLE_BASE` 两种，见 [启动方式](http://wiki.swoole.com/#/learn?id=swoole_process) | `SWOOLE_PROCESS`                        |
| `middleware_error_policy`     | 中间件错误处理策略，见 [中间件 - 错误处理策略](../../event/middleware/#_6) | 1                                       |
| `reload_delay_time`           | 框架 reload 重载命令接收后延迟的时间（毫秒，0 为不等待）     | 800                                     |
| `global_middleware_binding`   | 给注解事件绑定全局中间件，见 [中间件 - 全局中间件](../../event/middleware/#_6) | `[]`                                    |
| `save_console_log_file`   | 当这里输入字符串路径时，所有 `Console::xxx()` 输出的日志都将保存到目标文件 | false                                    |

### 子表 **light_cache**

| 配置名称                   | 说明                                            | 默认值                       |
| -------------------------- | ----------------------------------------------- | ---------------------------- |
| `size`                     | 最多可以缓存的 k-v 条目数（必须是 2 的 n 次方） | 512                         |
| `max_strlen`               | 作为 value 字符串的最大长度                     | 32768                        |
| `hash_conflict_proportion` | Hash冲突率（越大越好，但是需要的内存更多）      | 0.6                          |
| `persistence_path`         | 持久化的键值对的存储路径                        | `zm_data` 下的 `_cache.json` |
| `auto_save_interval`       | 持久化的键值对自动保存时间间隔（秒）            | 900                          |

### 子表 worker_cache

| 配置名称 | 说明                        | 默认值 |
| -------- | --------------------------- | ------ |
| `worker` | 跨进程缓存的存储工作进程 id | 0      |

### 子表 **mysql_config**

| 配置名称                 | 说明                           | 默认值                                                       |
| ------------------------ | ------------------------------ | ------------------------------------------------------------ |
| `host`               | 数据库地址(留空则不使用数据库) | 空                                                           |
| `port`               | 数据库端口                     | 3306                                                         |
| `username`           | 连接数据库的用户名             |                                                              |
| `dbname`           | 要连接的数据库名               |                                                              |
| `password`           | 数据库连接密码                 |                                                              |
| `options`            | PDO 数据库的 options 参数      | `[PDO::ATTR_STRINGIFY_FETCHES => false,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]` |
| `pool_size` | MySQL 连接池大小              | 64                                           |
| `charset` | MySQL 字符集              | `utf8mb4`                                           |

### 子表 **redis_config**

| 配置名称   | 说明                                       | 默认值 |
| ---------- | ------------------------------------------ | ------ |
| `host`     | Redis 服务器地址，留空则启动时不创建连接池 | 空     |
| `port`     | Redis 服务器端口                           | 6379   |
| `timeout`  | Redis 超时时间                             | 1      |
| `db_index` | Redis 要连接的数据库 index                 | 0      |
| `auth`     | 认证字符串                                 | 空     |

### 子表 static_file_server

| 配置名称         | 说明                   | 默认值                         |
| ---------------- | ---------------------- | ------------------------------ |
| `status`         | 是否开启静态文件服务器 | false                          |
| `document_root`  | 静态文件的根目录       | `{WORKING_DIR}/resources/html` |
| `document_index` | 默认索引的文件名列表   | `["index.html"]`               |

### 子表 onebot

| 配置名称                 | 说明                                                         | 默认值 |
| ------------------------ | ------------------------------------------------------------ | ------ |
| `status`                 | 是否开启 OneBot 标准机器人解析功能                           | true   |
| `single_bot_mode`        | 是否开启单机器人模式                                         | false  |
| `message_level`          | 机器人的 WebSocket 事件在 Swoole 原生事件 `@OnMessageEvent` 中的等级（越高说明越被优先处理） | 99     |
| `message_convert_string` | 是否将数组格式的消息转换为字符串以保证与旧版本的兼容性       | true   |
| `message_command_policy` | CQCommand命令匹配后执行流程，`interrupt` 为不执行后续 CQMessage，`continue` 为继续       | `interrupt`   |

### 子表 remote_terminal

| 配置名称 | 说明                                                         | 默认值      |
| -------- | ------------------------------------------------------------ | ----------- |
| `status` | 是否开启远程终端功能，见 [组件 - 远程终端](/component/remote-terminal) | false       |
| `host`   | 远程终端监听地址，为安全起见，默认值只允许本地回环地址（127.0.0.1） | `127.0.0.1` |
| `port`   | 远程终端监听的 TCP 端口                                      | 20002       |
| `token`  | 远程终端连接的令牌（如果为空（""）则不验证）                 | ""          |

### 子表 module_loader

| 配置名称 | 说明                                                         | 默认值      |
| -------- | ------------------------------------------------------------ | ----------- |
| `enable_hotload` | 是否开启热加载模块包的功能 | false       |
| `load_path`   | 模块包加载的目录地址 | `zm_data` 下的 `modules` |

## 多环境下的配置文件

炸毛框架的配置文件模块支持不同环境下的配置文件，主要结构为 `global.{环境}.php`。在一般情况下，炸毛框架默认从教程引导方式根据指令 `vendor/bin/start server` 启动的框架是不带环境控制的。这章将讲述如何根据不同的环境（development / staging / production）来编写配置文件。

### 使用环境参数

在启动框架时，额外增加参数 `--env` 可以指定当前的环境，从而使用不同的配置文件。现在框架支持以下几种环境： `development`，`staging`，`production`。

```bash
vendor/bin/start server --env=development
```

### 不同环境配置文件

由于框架默认只带有 `global.php` 文件，所以假设你现在需要区分开发环境和生产环境的配置，将 `global.php` 文件复制并重命名为 `global.development.php` 或 `global.production.php` 即可。

### 优先级

如果指定了 `--env` 环境参数：`global.{对应环境}.php` > `global.php`，如果两个配置文件都找不到则报错。

如果未指定 `--env` 环境参数：`global.php` > `global.development.php` > `global.staging.php` > `global.production.php`。

## 其他自定义配置文件

炸毛框架的全局配置文件为 `global.php`，为了让不同的开发者更好的二次开发或者集成更多功能，炸毛框架的配置文件模块也支持自己编写的其他 `*.php` 或 `*.json` 格式的配置文件。例如炸毛框架默认附带了 `file_header.json` 这个配置文件（用来返回各类文件扩展名对应的 `Content-Type` 头参数的表）。

使用也非常简单，我们先以 `.json` 格式为例，我们创建一个 `example_a.json` 文件在 `config/` 目录（和 `global.php` 一个文件夹下），并编写自己的任意配置内容：

```json
{
  "key1": "value1"
}
```

在框架中，启动后就会默认加载，使用只需要用以下方式即可：

```php
use ZM\Config\ZMConfig; # 先 use 再使用！
$r = ZMConfig::get("example_a", "key1"); # $r == "value1"
```

如果需要用到变量或其他动态的内容，可以使用 `.php` 格式的配置文件。这里还是以 `example_a.php` 来举例：

```php
<?php
$config['key1'] = "value1";
$config['starttime'] = time();
return $config;
```

使用方式同上：

```php
$r = ZMConfig::get("example_a", "key1"); # $r == "value1"
$time = ZMConfig::get("example_a", "starttime"); # $time == 服务器启动时间
```

同时，自定义配置文件也支持环境变量，例如：`example_a.development.json` 或 `example_a.production.php` 均可。
