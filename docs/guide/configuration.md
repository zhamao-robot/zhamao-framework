# 配置

炸毛框架的所有配置文件都存储在 `config/` 目录中。每个选项都带有文档，所以你可以查阅这些文件并熟悉可用的配置项。

一般来说，我们建议你优先查看 `config/global.php` 文件及其文档，它包含了运行框架所需要的绝大部分配置，例如通信方式和时区等。

## global.php 配置说明

此处仅说明 `global.php` 当中较为重要的 `driver` 和 `servers` 配置项，其他配置项请参考 `config/global.php` 文件的注释。

```php
/* 启动框架的底层驱动（原生支持 swoole、workerman 两种） */
$config['driver'] = env('DRIVER', 'workerman');

/* 要启动的服务器监听端口及协议 */
$config['servers'] = [
    [
        'host' => '0.0.0.0',
        'port' => 20001,
        'type' => 'websocket',
    ],
    [
        'host' => '0.0.0.0',
        'port' => 20002,
        'type' => 'http',
        'flag' => 20002,
    ],
];
```

### driver

`driver` 配置项用于指定框架的底层驱动，目前支持以下驱动：

- `swoole`：基于 Swoole，需要安装 `swoole` 扩展，协程支持很好
- `workerman`：基于 Workerman，无需安装额外扩展，但在 PHP 8.0 下无法使用协程
- `choir`：基于 Choir，尚未完成，不建议使用

### servers

`servers` 配置项用于指定框架的服务器（[通信方式](https://12.onebot.dev/connect/)）配置，目前支持以下服务器：

- `http`：基础的 HTTP 服务（监听传入的 HTTP 请求）
    - `host`：监听地址
    - `port`：监听端口
    - `flags`：一个 int 值，用于在事件中获取源于哪一个 HTTP 监听的地址和端口
- `websocket`：WebSocket 服务（允许其他客户端接入）
    - `host`：监听地址
    - `port`：监听端口
    - `flags`：同上方 HTTP 的 `flags`

以上所有服务器均支持 `access_token` 配置项，用于指定[鉴权方式](/components/bot/authorization.md)。

------

## 配置文件格式

除了 `php` 文件外，我们还支持以下格式：

- YAML
- JSON
- TOML

## 环境配置

根据运行的环境采用不同的配置值是很有必要的，例如你可能希望在本地和生产环境中使用不同的数据库配置。

为了方便这一功能，我们提供了基于环境的配置加载措施。你可以在启动框架时使用 `--env`
选项指定当前环境，例如 `./zhamao server --env=production` 。

如果未明确指定当前环境，则默认环境为 `development`。

如果要使某一配置文件只在特定环境下记载，你可以为给文件添加后缀，例如 `example.production.php`
只会在当前环境为 `production` 时加载。

你可以同时设置基础配置文件和环境特定配置文件，例如 `global.php` 和 `global.production.php` ，后者将会覆盖前者中的配置项。

### 配置安全

我们**非常不建议**你将数据库信息、访问密钥的敏感字段提交给版本控制，因为这可能会导致相关信息的泄露，同时也不利于其他开发人员修改自己的本地配置。

相反，我们建议你借助 DotEnv 等库，将相关配置信息写入 `.env` 文件中，并按需读取。

你可以选择使用下方的环境变量来区分不同环境的配置，并提高安全性。

## 访问配置项

你可以在任何地方使用全局 `config()` 函数获取配置项值。支持使用点语法获取，配置项名称由文件名开头，并允许指定默认值。

```php
# 获取时区，未设置则返回 Asia/Shanghai
$value = config('global.runtime.timezone', 'Asia/Shanghai');
```

你也可以使用 `config` 函数设置配置项，传递数组即可：

```php
# 将时区修改为 UTC
config(['global.runtime.timezone' => 'UTC']);
```

## 环境变量

在不同的环境中使用不同的配置是很有必要的，例如你可能希望在本地和生产环境中使用不同的数据库配置。

然而，你可能不希望将这些敏感信息（例如生产环境的密钥、数据库配置等）提交到版本控制中，因此我们提供了基于环境变量的配置加载措施。

简单来说，你可以在 `.env` 文件中设置环境变量，然后在配置文件中使用 `env()` 函数获取环境变量的值。

需要注意的是，`.env` 文件中的所有变量均可被外部环境变量覆盖（例如系统级或 Docker 等容器环境变量）。

::: warning 注意
我们**强烈不建议**你将 `.env` 文件提交到版本控制中，因为这可能会导致相关信息的泄露，同时也不利于其他开发人员修改自己的本地配置。

我们建议你将 `.env` 文件添加到 `.gitignore` 中，或者使用其他方式（例如 Doppler 等服务或是 Docker 等容器）来管理环境变量。

你可以通过保留 `.env.example` 文件来提供其他开发人员参考。
:::

### 环境变量类型转换

环境变量默认都是字符串类型，但框架内部会自动将其转换为其他类型以便使用，转换表格如下：

| 原始值       |   转换后   |
|-----------|:-------:|
| `true`    | `true`  |
| `(true)`  | `true`  |
| `false`   | `false` |
| `(false)` | `false` |
| `null`    | `null`  |
| `(null)`  | `null`  |
| `empty`   |  `''`   |
