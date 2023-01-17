# KV 缓存

在部分场景中，我们会希望将一些数据缓存起来，以便后续使用，例如查询数据或处理任务的操作，通过缓存数据以避免反复查询，加速处理。

这些缓存数据通常储存在极快的存储系统中，例如 Redis，Memcached 等。

框架在 PSR-16 规范的基础上扩展出了 `KVInterface` 接口，提供使用特定缓存库的能力。

## 配置

你可以通过 `global.php` 中的 `kv` 配置项来配置缓存库。

```php
/* KV 数据库的配置 */
$config['kv'] = [
    'use' => \LightCache::class,                        // 默认在单进程模式下使用 LightCache，多进程需要使用 KVRedis
    'light_cache_dir' => $config['data_dir'] . '/lc',   // 默认的 LightCache 保存持久化数据的位置
    'light_cache_autosave_time' => 600,                 // LightCache 自动保存时间（秒）
    'redis_config' => 'default',                        // 在使用 KVRedis 时使用的 redis 连接配置的名称
];
```

## 支持驱动

目前，框架内置了两种缓存库的驱动：

- `LightCache`：单进程模式下默认使用的缓存库，使用文件系统作为存储介质，支持持久化。
- `KVRedis`：多进程模式下使用的缓存库，使用 Redis 作为存储介质，不支持持久化（取决于 Redis 的配置）。

你也可以通过实现 `KVInterface` 接口来自定义缓存库的驱动。

## 使用

你可以通过 `kv` 函数来获取缓存库的实例。

```php
kv()->set('key', 'value');
```

可以为 `kv` 函数传递一个参数来获取指定的缓存库。

```php
kv('user')->set('key', 'value');
kv('user')->get('key', 'default_value'); // 返回 value
```

缓存组件完全遵循 PSR-16 规范，因此你可以参考 [PSR-16](https://www.php-fig.org/psr/psr-16/) 来使用缓存组件。

## 使用场景

KV 库常用于一些轻量级内容的存储，例如一些插件的配置项、运行状态、统计计数等场景。
默认在单进程模式下使用的 `LightCache` 读写为全局变量模式，无任何 IO。
多进程下如果选择 `KVRedis` 作为 KV 库驱动，则性能取决于和 Redis 服务器之间通信的性能。

例如，在编写机器人插件时，需要初始化一个插件的配置项，假设是一个机器人管理员模块，需要设置当前的超级管理员 ID 号：

```php
#[\Init()]
public function init()
{
    if (!kv('my-admin-plugin')->has('admin-id')) {
        kv('my-admin-plugin')->set('admin-id', '456789');
    }
}
```
