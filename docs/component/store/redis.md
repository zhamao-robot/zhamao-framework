# Redis

炸毛框架内置了 Redis 连接池，供开发者使用。使用前需要先安装 `redis` 扩展：

```bash
pecl install redis
```

> 如果是 Docker 环境，则默认已安装。

## 配置

配置文件在 `config/global.php` 的全局配置文件下，详情见 [配置](/guide/basic-config/#redis_config)。

示例配置（假设 Redis Server 开到了本地）：

```php
/** Redis连接信息，host留空则启动时不创建Redis连接池 */
$config['redis_config'] = [
    'host' => '127.0.0.1',
    'port' => 6379,
    'timeout' => 1,
    'db_index' => 0,
    'auth' => ''
];
```

## 使用

当写好配置文件后，不可以使用 reload 进行重载，因为连接池需要在主进程中声明配置，才可以应用到多个工作进程中。所以必须输入 `stop` 或 Ctrl+C 停止后再启动框架。

定义：`ZM\Store\Redis\ZMRedis`

因为使用的是连接池，所以每次使用完一个连接需要归还连接给连接池。框架封装了两种方式自动归还，你可以选择下面其中的任意一种。

以下的方式获取的 `$redis` 都是 `redis` 扩展的对象 `\Redis`，关于 redis 扩展的方法文档，详情见：[Redis 文档](https://www.php.cn/course/49.html)。

### 对象模式

```php
$obj = new ZMRedis();
$redis = $obj->get();
ctx()->reply($redis->ping("123"));
```

### 回调模式

```php
// 前面的代码
ZMRedis::call(function($redis) {
    $redis->set("key1", "hello world");
    $result = $redis->get("key1");
    ctx()->reply($result);
});
// 后面的代码
```

### 二者的区别

选一个喜欢的就好。硬要是说区别的话，对象模式是在 PHP 自动回收这个 `ZMRedis` 对象时会归还连接，也可以通过手动 `unset($obj)` 进行回收，否则就会执行到函数结尾自动回收。切记不可将 `$obj` 对象持久化存到静态或全局变量等。

回调模式看似是回调，但是是同步执行的，不会发生顺序错乱。也就是说到了 `ZMRedis::call()` 方法里面的时候，后面的代码不会提前执行，是顺序执行的。回调的作用仅仅是用作自动回收连接对象。