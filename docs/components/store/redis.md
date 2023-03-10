# Redis 数据库

Redis 是一个开源的可基于内存亦可持久化的高性能键值对数据库。

## 配置

你可以通过 `global.php` 中的 `redis` 配置项来配置 Redis 数据库。

```php
/* Redis 连接配置，框架将自动生成连接池，支持多个连接池 */
$config['redis'] = [
    'default' => [
        'enable' => false,
        'host' => '127.0.0.1',
        'port' => 6379,
        'index' => 0,
        'auth' => '',
        'pool_size' => 10,
    ],
];
```

你可以在配置中定义多个 Redis 连接，每个连接都有一个唯一的名称，例如 `default`，`cache`，`session` 等。

## 使用

你可以使用 `redis` 函数来获取 Redis 连接，例如：

```php
$redis = redis('default');
$redis->set('key', 'value');
```

`redis` 函数接收一个参数，即 Redis 连接的名称，如果不传递参数，则默认使用 `default` 连接。其会返回一个 `RedisWrapper`
对象，该对象是对 Redis 连接池的封装。

你可以通过 `RedisWrapper` 中的各种方法来操作 Redis 数据库，例如 `set`，`get`，`del` 等。

详情请参考 [redis 全局函数](/components/common/global-defines.html#redis)。

## 连接池

框架会自动为每个 Redis 连接创建一个连接池，你可以通过 `pool_size` 配置项来设置连接池的大小。

## 通过 KV 库方式使用 Redis

默认情况下，你使用 `redis()` 方法获取的是 redis 扩展的原生操作对象，使用方式和传统的 redis 扩展完全相同。

框架实现了一个 KVInterface 接口，继承于 PSR SimpleCache 标准，你可以使用 PSR-16 的方式来使用其中一个 redis 库。详见 [KV 库](/components/store/cache)。
