# KV 缓存

在部分肠粉中，我们会希望将一些数据缓存起来，以便后续使用，例如查询数据或处理任务的操作，通过缓存数据以避免反复查询，加速处理。

这些缓存数据通常储存在极快的存储系统中，例如 Redis，Memcached 等。

框架在 PSR-16 规范的基础上扩展出了 `KVInterface` 接口，提供使用特定缓存库的能力。

## 配置

你可以通过 `global.php` 中的 `kv` 配置项来配置缓存库。

```php
/* KV 数据库的配置 */
$config['kv'] = [
    'use' => \LightCache::class,                        // 默认在单进程模式下使用 LightCache，多进程需要使用 ZMRedis
    'light_cache_dir' => $config['data_dir'] . '/lc',   // 默认的 LightCache 保存持久化数据的位置
    'light_cache_autosave_time' => 600,                 // LightCache 自动保存时间（秒）
    'redis_config' => 'default',
];
```

### 驱动

目前，框架内置了两种缓存库的驱动：

- `LightCache`：单进程模式下使用的缓存库，使用文件系统作为存储介质，支持持久化。
- `ZMRedis`：多进程模式下使用的缓存库，使用 Redis 作为存储介质，不支持持久化（取决于 Redis 的配置）。

你可以通过实现 `KVInterface` 接口来自定义缓存库的驱动。

## 使用

你可以通过 `kv` 函数来获取缓存库的实例。

```php
kv()->set('key', 'value');
```

为 `kv` 函数传递一个参数来获取指定的缓存库。

```php
kv('user')->set('key', 'value');
```

缓存组件完全遵循 PSR-16 规范，因此你可以参考 [PSR-16](https://www.php-fig.org/psr/psr-16/) 来使用缓存组件。
