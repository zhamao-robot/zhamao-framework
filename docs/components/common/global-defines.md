# 全局方法和常量

## 全局常量

### 框架基本全局常量

| 常量名                    | 含义                             | 值                                                                                     |
|------------------------|--------------------------------|---------------------------------------------------------------------------------------|
| `ZM_VERSION_ID`  <br/> | 炸毛框架的版本 ID，类似 `PHP_VERSION_ID` | 随版本变化，例如 `30100`                                                                      |
| `ZM_VERSION`           | 炸毛框架的版本                        | 随版本变化，例如 `3.0.1`                                                                      |
| `LOAD_MODE`            | 框架的启动加载模式                      | 由框架的安装方式而定，值可能为 `LOAD_MODE_VENDOR` 或 `LOAD_MODE_SRC`                                  |
| `LOAD_MODE_VENDOR`     | 从 `vendor` 目录加载框架，用于开发者开发插件时使用 | `0`                                                                                   |
| `LOAD_MODE_SRC`        | 从 `src/ZM` 目录加载框架，用于开发者开发框架时使用 | `1`                                                                                   |
| `WORKING_DIR`          | 框架的工作目录                        | 根据用户启动框架时命令行的当前目录变化                                                                   |
| `SOURCE_ROOT_DIR`      | 框架的运行根目录                       | 如果是 Phar 启动，则为 Phar 根目录，否则根据用户启动框架时命令行的当前目录变化                                         |
| `FRAMEWORK_ROOT_DIR`   | 框架自身的源代码主目<br/>录               | 如果是 vendor 目录加载框架，则为 `vendor/zhamao/framework/`，否则等于 `SOURCE_ROOT_DIR`                |
| `TMP_DIR`              | 框架可使用的临时文件夹                    | Windows 下为 `C:\Windows\Temp`，定义了 `TMPDIR` 系统环境变量则使用其值，否则可能是 `/tmp` 或工作目录的 `./.zm-tmp` |
| `ZM_INIT_TIME`         | 引入框架启动的最早时间戳（毫秒级）              | `microtime(true)` 的值                                                                  |
| `ZM_STATE_DIR`         | 存放框架各进程和连接状态的目录                | `TMP_DIR` 下的 `sha1(ZM_INIT_TIME . FRAMEWORK_ROOT_DIR)` 子目录                            |
| `APP_VERSION`          | 使用框架开发的项目的版本                   | 需开发者自行定义，如果在启动框架前未指定，则自动使用框架版本                                                        |

### 多进程全局常量

| 常量名                     | 含义            |
|-------------------------|---------------|
| `ZM_PROCESS_MASTER`     | Master 进程     |
| `ZM_PROCESS_MANAGER`    | Manager 进程    |
| `ZM_PROCESS_WORKER`     | Worker 进程     |
| `ZM_PROCESS_USER`       | User 进程       |
| `ZM_PROCESS_TASKWORKER` | TaskWorker 进程 |

### 错误全局常量

| 常量名                               | 含义      |
|-----------------------------------|---------|
| `ZM_ERR_NONE`                     | 无错误     |
| `ZM_ERR_METHOD_NOT_FOUND`         | 找不到方法   |
| `ZM_ERR_ROUTE_NOT_FOUND`          | 找不到路由   |
| `ZM_ERR_ROUTE_METHOD_NOT_ALLOWED` | 路由方法不允许 |

### 机器人回复模式常量

> 该类常量可以使用位运算叠加效果，例如既 at 又引用：`ZM_REPLY_MENTION | ZM_REPLY_QUOTE`。

| 常量名                | 含义          |
|--------------------|-------------|
| `ZM_REPLY_NONE`    | 默认回复，不带任何东西 |
| `ZM_REPLY_MENTION` | 回复时 at 该用户  |
| `ZM_REPLY_QUOTE`   | 回复时引用该消息    |

### 机器人询问模式常量

> 该类常量可以使用位运算叠加效果，例如询问时引用，超时时 at：`ZM_PROMPT_QUOTE_USER | ZM_RPOMPT_TIMEOUT_MENTION_USER`

| 常量名                              | 含义                                                         |
|----------------------------------|------------------------------------------------------------|
| `ZM_PROMPT_NONE`                 | 使用 `prompt()` 时不附加任何内容                                     |
| `ZM_PROMPT_MENTION_USER`         | 询问时 at 该用户                                                 |
| `ZM_PROMPT_QUOTE_USER`           | 询问时引用该用户的消息                                                |
| `ZM_PROMPT_TIMEOUT_MENTION_USER` | 询问超时，回复超时语句时 at 该用户                                        |
| `ZM_PROMPT_TIMEOUT_QUOTE_SELF`   | 询问超时，发送超时语句时引用自己的询问语句（如果存在）                                |
| `ZM_PROMPT_TIMEOUT_QUOTE_USER`   | 询问超时，发送超时语句时引用用户自己的消息（与 `ZM_PROMPT_TIMEOUT_QUOTE_SELF` 互斥） |    

## 全局方法

全局方法一般是一些工具类函数，例如快速输出 log、打印变量、获取上下文对象等。

::: warning 注意

框架的全局方法均需要在合适的地方使用，在非法的位置会出现问题、抛出异常或返回 null。例如：

- 在框架启动前调用 `logger()` 可能会无法应用 Logger 等级。
- 在非机器人相关的事件内（如 Route 路由事件）获取机器人上下文默认返回一个空对象，需要进一步调用 `bot()->getBot()` 方法获取具体的机器人上下文。

:::

### zm_dir()

根据具体操作系统替换路径的分隔符。

```php
// Windows 系统
$real_path = zm_dir('C:/Windows/win.ini');  // 返回 "C:\\Windows\\win.ini"
// Phar 环境下，双系统都是正斜杠
$real_path = zm_dir('phar://C:/a.php'); // 返回相同的内容
// Linux 环境
$real_path = zm_dir('asd/bbb'); // 返回相同的内容
```

### zm_exec()

执行 shell 命令，如果在协程环境下，将自动运用协程挂起。

- 定义：`zm_exec(string $cmd)`
- 返回：`OneBot\Driver\Process\ExecutionResult`

```php
$result = zm_exec('uname -s');
echo $result->stdout . PHP_EOL; // 返回 输出内容
echo $result->stderr . PHP_EOL; // 返回 STDERR 内容
echo $result->code === 0 ? '正常退出' : '异常退出'; // 返回的状态码
```

### zm_sleep()

sleep 指定事件，单位为秒（最小单位为 1 毫秒，即 0.001）。在协程环境下不会阻塞进程地睡眠。

- 定义：`zm_sleep(float|int $time)`

```php
zm_sleep(2.5);
```

### coroutine()

获取协程接口，调用协程相关的 API。如果协程环境不支持或未就绪，返回 null。

- 返回：`OneBot\Driver\Coroutine\CoroutineInterface|null`

### zm_instance_id()

返回当前炸毛框架运行实例的 ID。

当前实例的 ID 计算方法为：当前时间戳（毫秒级）的 CRC32 的十六进制值。

```php
echo zm_instance_id(); // 返回当前实例的 ID，例如 bd22f36f
```

### logger()

助手方法，返回一个框架绑定的 Logger 实例，符合 PSR-Logger 标准。

- 返回：`Psr\Log\LoggerInterface`

```php
logger()->info('你好');
logger()->warning('警告');
logger()->error('错误');
```

### is_assoc_array()

判断传入的数组是否为键值对数组。

- 定义：`is_assoc_array(array $array)`
- 返回：`bool`

```php
var_dump(is_assoc_array([1, 2, 3])); // 返回 false
var_dump(is_assoc_array(['a' => 1, 'b' => 2])); // 返回 true
```

### match_pattern()

星号通配符匹配字符串。星号以外的部分必须完全匹配才会匹配成功。

- 定义：`match_pattern(string $pattern, string $subject)`
- 返回：`bool`

参数说明：

- `$pattern` 为匹配的表达式，例如 `我叫*，今年*岁了。`
- `$subject` 为内容，例如 `我叫小明，今年19岁了。`

```php
var_dump(match_pattern('我叫*，今年*岁了。', '我叫小明，今年19岁了。')); // 返回 true
var_dump(match_pattern('我叫*，今年*岁了。', '我叫小明，今年19岁了')); // 返回 false
```

### segment()

构建消息段的助手函数。

- 定义：`segment(string $type, array $data = [])`
- 返回：`MessageSegment`

参数同 `MessageSegment` 对象，这里不做多余的说明。

```php
$segment = segment('text', ['text' => 'hello']);
bot()->reply([$segment]); // 发送一条 hello 的消息
$seg2 = segment('mention', ['user_id' => '123456']);
bot()->reply([$seg2, $segment]); // 发送带 at
```

### middleware()

中间件操作类的助手函数。

返回：`ZM\Middleware\MiddlewareHandler`

```php
middleware()->process(function() {});
```

### container()

获取容器实例。

返回：`DI\Container`

```php
container()->get('xxx');
```

### resolve()

解析类实例（使用容器），相当于 `container()->make()` 的别名。

- 定义：`resolve(string $abstract, array $parameters = [])`
- 返回：`Closure|T`（返回类的实例对象或闭包）

### db()

获取 Database 数据库连接操作类。

- 定义：`db(string $name = '')`
- 返回：`ZM\Store\Database\DBWrapper`

这里返回的是炸毛 SQL 类数据库操作的 wrapper 类，name 为全局配置中对应的名称。

我们假设连接了一个 MySQL 数据库，假设数据库配置如下：

```php
$config['database'] = [
    'mydb' => [
        'enable' => true,
        'type' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'root',
        'password' => 'ZhamaoTEST',
        'dbname' => 'zm',
        'charset' => 'utf8mb4',
        'pool_size' => 64,
    ]
];
```

在插件中使用：

```php
$result = db('mydb')->fetchAllAssociative('SELECT * FROM users WHERE username = ?', ['jerry']);
var_dump($result[0]); // 假设数据库表只有 id 和 username 两列，这里返回了 ['id' => 1, 'username' => 'jerry']
```

有关此处数据库更详细的内容，请看 [SQL 数据库组件](/components/store/mysql.md)。

### zm_sqlite()

> 仅限于炸毛框架 3.2.0 及以上版本使用。

获取一个便捷 SQLite 模式的数据库操作对象。

有关此处数据库更详细的内容，请看 [SQL 数据库组件](/components/store/mysql.md)。

### sql_builder()

使用 SQL 语句构建器构建一个查询。

- 定义：`sql_builder(string $name = '')`
- 返回：`ZM\Store\Database\DBQueryBuilder`

这里返回的是炸毛 SQL 类数据库操作的查询构建器类，name 为全局配置中对应的名称。

我们再次假设数据库配置同上方 `db()` 中提到的配置相同，使用 `sql_builder()`：

```php
$result = sql_builder('mydb')->select('*')->from('users')->where('username = :username')->setParameter('username', 'jerry')->execute()->fetchAllAssociative();
// 结果与上方相同
```

有关此处数据库更详细的内容，请看 [SQL 数据库组件](/components/store/mysql.md)。

### zm_sqlite_builder()

> 仅限于炸毛框架 3.2.0 及以上版本使用。

获取一个便捷 SQLite 模式的数据库 SQL 语句构造器。

有关此处数据库更详细的内容，请看 [SQL 数据库组件](/components/store/mysql.md)。

### redis()

获取 Redis 操作类。有关 Redis 的更多详情和配置，见 [Redis 数据库组件](/components/store/redis)。

- 定义：`redis(string $name = 'default')`
- 返回：`ZM\Store\KV\Redis\RedisWrapper`

返回的 RedisWrapper 对象为 phpredis 扩展的 `\Redis` 对象的 wrapper 类，此类的方法等同于 `\Redis` 类。有关 phpredis 类的方法文档，
见 [Redis - 官方文档](https://phpredis.github.io/phpredis/) 或 [中文文档（非官方）](https://www.cnblogs.com/ikodota/archive/2012/03/05/php_redis_cn.html)。

```php
redis('myredis')->set('hello', 'world');
$value = redis('myredis')->get('hello'); // 返回 world
```

其中，名称默认为 `default`，即如果你的配置文件中设置的 Redis 连接名称存在 `default`，将默认返回该连接的实例。

```php
/* Redis 连接配置，框架将自动生成连接池，支持多个连接池 */
$config['redis'] = [
    'default' => [
        'enable' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'index' => 0,
        'auth' => '',
        'pool_size' => 10,
    ],
];
```

```php
// 不加 name 默认使用 default 连接
redis()->del('hello');
```

### config()

获取或设置配置项。

- 定义：`config(array|string $key = null, mixed $default = null)`
- 返回：`mixed|ZM\Config\ZMConfig|void`

当 `$key` 传入 `string` 时，表明从配置中获取参数。有关如何获取配置项，见 [组件 - 配置文件（TODO）]()。

当 `$key` 传入的是数组，则表明是修改配置内容，例如：`config(['global.driver' => 'swoole'])` 表明修改 `global` 配置下的 `driver` 项为 `swoole`。

当 `$key` 没有指定或传入 null，则返回 ZMConfig 实例对象。

当 `$key` 传入 `string` 来获取配置项时，如果配置项不存在，则返回 `$default` 值。

```php
$result = config('global.driver'); // 假设返回 swoole
$result = config('global.dohewiufew'); // 不存在配置项，返回 null
$result = config('global.fonnwe', 'niu'); // 不存在配置项，设置了默认值，则返回默认值，这里会返回 'niu'

config()->set('global.driver', 'swoole'); // 修改配置
$result = config()->get('global.driver', 'swoole'); // 获取配置，和直接传入 string 相同效果
```

### bot()

获取当前事件的机器人上下文操作对象。

返回：`ZM\Context\BotContext`

此方法和依赖注入不同的是，无论所在事件是否为机器人事件，均会返回上下文对象，不会报错，但返回的对象不能做任何机器人操作，需要开发者调用方法 `getBot()` 获取指定的机器人上下文。

这里我们假设两个场景，第一个是使用机器人的 BotCommand 完成一个机器人的命令回复：

```php
#[\BotCommand(match: '测试上下文')]
public function testCtx(\BotContext $ctx)
{
    $ctx->reply('你好啊');
}
```

上方的例子是第一章指南中给我们的例子，使用了依赖注入功能，获取到了上下文对象。如果你不喜欢依赖注入，就可以使用 `bot()` 全局方法代替：

```php
#[\BotCommand(match: '测试上下文')]
public function testCtx()
{
    bot()->reply('你好啊');
}
```

第二个场景，某个人访问了框架的某个 Route 路由，我们想调用机器人发送一条消息。这时候依赖注入使用 `BotContext` 会抛出异常，无法使用。
使用 `bot()` 则不会。

下面这个例子就是在访问一个路由的时候调用机器人给一个用户发送一条私聊消息。我们假设机器人的平台是 qq，机器人的 QQ 号为 123456。

> 你首先需要保证机器人实现端已经通过反向 WebSocket 方式连接到框架并且可以正常收发消息。

```php
#[\Route('/test')]
public function testRoute(HttpRequestEvent $event)
{
    if ($event->getRequest()->getMethod() === 'POST') {
        $contents = $event->getRequest()->getBody()->getContents();
        // 将收到的 POST 请求用机器人私发给某人
        bot()->getBot('123456', 'qq')->sendMessage('收到了一个 POST 请求：' . $contents, 'private', ['user_id' => '67867867']);
    } else {
        // 这个路由其他 method 都不允许，只能 POST，其他请求过来就返回 405 Not Allowed
        $event->withResponse(\Choir\Http\HttpFactory::createResponse(405));
    }
}
```

### kv()

获取一个 KV 库实例。

有关 KV 库的使用，见 [组件 - KV 缓存](/components/store/cache)。

### zm_http_response()

快速生成一个符合 PSR-7 的 HTTP Response 对象。

有关参数，等同于 HttpFactory 对象，详见 HttpFactory 文档（TODO）。

### ws_socket()

获取驱动的 WebSocket 操作对象。

定义：`function ws_socket(int $flag = 1): WSServerResponse`

传入一个 flag 值（值为你在 `global.php` 中为 server 设置的 flag 值），返回对应端口的 WebSocket 操作对象。

操作对象可以主动发送消息到指定客户端、可以获取指定端口的配置信息等。

```php
$socket = ws_socket();
$socket->send('hello world', $event->getFd()); // 客户端的连接 fd 编号可以通过 WebSocketOpenEvent 等事件获取
```

### zm_create_app()

创建一个炸毛框架的单文件应用（仅单文件，项目外非编写插件模式时可用），效果等同于 `new \ZM\ZMApplication()`。

### zm_create_plugin()

创建一个炸毛单文件模式的插件对象，效果等同于 `new \ZM\Plugin\ZMPlugin()`。

### zm_websocket_client()

创建一个 WebSocket 客户端。详情见 [框架内置 WebSocket 客户端](/components/http/websocket-client)。
