# 基本配置

到目前为止，炸毛框架的配置文件还没有任何变更，是默认的行为。在本章内容中，将列举出炸毛框架的配置文件的规则和使用。

!!! error "警告"

    因为炸毛框架的全局配置中含有数据库名称和密码以及 access_token 等敏感字段，在使用版本控制软件过程中请不要将将敏感信息写入配置文件并提交至开源仓库！

## 全局配置文件 global.php

框架的全局配置文件在 `config/global.php` 文件中。下面是配置文件的各个选项，请根据自己的需要自行配置。

| 配置名称                     | 说明                                             | 默认值                       |
| :--------------------------- | ------------------------------------------------ | ---------------------------- |
| `host`                       | 框架监听的地址                                   | 0.0.0.0                      |
| `port`                       | 框架监听的端口                                   | 20001                        |
| `http_reverse_link`          | 框架开到公网或外部的 HTTP 反代链接               | 见配置文件                   |
| `zm_data`                    | 框架的配置文件、日志文件等文件目录               | `./` 下的 `zm_data/`         |
| `debug_mode`                 | 框架是否启动 debug 模式                          | false                        |
| `crash_dir`                  | 存放崩溃和运行日志的目录                         | `zm_data` 下的 `crash/`      |
| `swoole`                     | 对应 Swoole server 中 set 的参数，参考Swoole文档 | 见子表 `swoole`              |
| `light_cache`                | 轻量内置 key-value 缓存                          | 见字表 `light_cache`         |
| `sql_config`                 | MySQL 数据库连接信息                             | 见子表 `sql_config`          |
| `redis_config`               | Redis 连接信息                                   | 见子表 `redis_config`        |
| `access_token`               | OneBot 客户端连接约定的token，留空则无           | 空                           |
| `http_header`                | HTTP 请求自定义返回的header                      | 见配置文件                   |
| `http_default_code_page`     | HTTP服务器在指定状态码下回复的默认页面           | 见配置文件                   |
| `init_atomics`               | 框架启动时初始化的原子计数器列表                 | 见配置文件                   |
| `info_level`                 | 终端日志显示等级（0-4）                          | 2                            |
| `context_class`              | 上下文所定义的类，待上下文完善后见对应文档       | `\ZM\Context\Context::class` |
| `static_file_server`         | 静态文件服务器配置项                             | 见子表 `static_file_server`  |
| `server_event_handler_class` | 注册 Swoole Server 事件注解的类列表              | 见配置文件                   |
| `command_register_class`     | 注册自定义命令行选项指令的类                     | 见配置文件                   |
| `modules`                    | 服务器启用的外部第三方和内部插件                 | `['onebot' => true]`         |

### 子表 **swoole**

| 配置名称        | 说明                                                         | 默认值                              |
| --------------- | ------------------------------------------------------------ | ----------------------------------- |
| `log_file`      | Swoole 的日志文件                                            | `crash_dir` 下的 `swoole_error.log` |
| `worker_num`    | Worker 工作进程数                                            | 运行框架的主机 CPU 核心数           |
| `dispatch_mode` | 数据包分发策略，见 [文档](https://wiki.swoole.com/#/server/setting?id=dispatch_mode) | 2                                   |
| `max_coroutine` | 最大协程并发数                                               | 300000                              |

### 子表 **light_cache**

| 配置名称                   | 说明                                            | 默认值                       |
| -------------------------- | ----------------------------------------------- | ---------------------------- |
| `size`                     | 最多可以缓存的 k-v 条目数（必须是 2 的 n 次方） | 1024                         |
| `max_strlen`               | 作为 value 字符串的最大长度                     | 16384                        |
| `hash_conflict_proportion` | Hash冲突率（越大越好，但是需要的内存更多）      | 0.6                          |
| `persistence_path`         | 持久化的键值对的存储路径                        | `zm_data` 下的 `_cache.json` |
| `auto_save_interval`       | 持久化的键值对自动保存时间间隔（秒）            | 900                          |

### 子表 **sql_config**

| 配置名称                 | 说明                           | 默认值                                                       |
| ------------------------ | ------------------------------ | ------------------------------------------------------------ |
| `sql_host`               | 数据库地址(留空则不使用数据库) | 空                                                           |
| `sql_port`               | 数据库端口                     | 3306                                                         |
| `sql_username`           | 连接数据库的用户名             |                                                              |
| `sql_database`           | 要连接的数据库名               |                                                              |
| `sql_password`           | 数据库连接密码                 |                                                              |
| `sql_options`            | PDO 数据库的 options 参数      | `[PDO::ATTR_STRINGIFY_FETCHES => false,PDO::ATTR_EMULATE_PREPARES => false]` |
| `sql_default_fetch_mode` | PDO 的 fetch 模式              | `PDO::FETCH_ASSOC`                                           |

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

## 多环境下的配置文件

炸毛框架的配置文件模块支持不同环境下的配置文件，主要结构为 `global.{环境}.php`。在一般情况下，炸毛框架默认从教程引导方式根据指令 `vendor/bin/start server` 启动的框架是不带环境控制的。这章将讲述如何根据不同的环境（production / development / staging）来编写配置文件。

### 使用环境参数

在启动框架时，额外增加参数 `--env` 可以指定当前的环境，从而使用不同的配置文件。现在框架支持以下几种环境： `production`，`staging`，`development`。

```bash
vendor/bin/start server --env=development
```

### 不同环境配置文件

由于框架默认只带有 `global.php` 文件，所以假设你现在需要区分开发环境和生产环境的配置，将 `global.php` 文件复制或改名为 `global.development.php` 或 `global.production.php` 即可。

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