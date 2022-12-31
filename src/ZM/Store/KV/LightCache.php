<?php

declare(strict_types=1);

namespace ZM\Store\KV;

use Psr\SimpleCache\CacheInterface;
use ZM\Exception\InvalidArgumentException;
use ZM\Process\ProcessStateManager;
use ZM\Store\FileSystem;

/**
 * 轻量、基于本地 JSON 文件的 KV 键值对缓存
 */
class LightCache implements CacheInterface, KVInterface
{
    /** @var array 存放库对象的列表 */
    private static array $objs = [];

    /** @var array 存放缓存数据的列表 */
    private static array $caches = [];

    /** @var array 存放超时数据的列表 */
    private static array $ttys = [];

    /** @var string 查找库的目录地址 */
    private string $find_dir;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(private string $name = '', string $find_str = '')
    {
        if ((ProcessStateManager::$process_mode['worker'] ?? 0) > 1) {
            logger()->error('LightCache 不支持多进程模式，如需在多进程下使用，请使用 ZMRedis 作为 KV 引擎！');
            return;
        }
        $this->find_dir = empty($find_str) ? config('global.kv.light_cache_dir', '/tmp/zm_light_cache') : $find_str;
        FileSystem::createDir($this->find_dir);
        $this->validateKey($name);
        if (file_exists($this->find_dir . '/' . $name . '.json')) {
            $data = json_decode(file_get_contents($this->find_dir . '/' . $name . '.json'), true);
            if (is_array($data)) {
                self::$caches[$name] = $data['data'];
                self::$ttys[$name] = $data['expire'];
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function open(string $name = ''): CacheInterface
    {
        if (!isset(self::$objs[$name])) {
            self::$objs[$name] = new LightCache($name);
        }
        return self::$objs[$name];
    }

    /**
     * @throws \JsonException
     */
    public static function saveAll()
    {
        /** @var LightCache $obj */
        foreach (self::$objs as $obj) {
            $obj->save();
        }
        logger()->debug('Saved all light caches');
    }

    /**
     * 保存 KV 库的数据到文件
     *
     * @throws \JsonException
     */
    public function save(): void
    {
        file_put_contents(zm_dir($this->find_dir . '/' . $this->name . '.json'), json_encode([
            'data' => self::$caches[$this->name] ?? [],
            'expire' => self::$ttys[$this->name] ?? [],
        ], JSON_THROW_ON_ERROR));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // 首先判断在不在缓存变量里
        if (!isset(self::$caches[$this->name][$key])) {
            return $default;
        }
        // 然后判断是否有延迟
        if (isset(self::$ttys[$this->name][$key])) {
            if (self::$ttys[$this->name][$key] > time()) {
                return self::$caches[$this->name][$key];
            }
            unset(self::$ttys[$this->name][$key], self::$caches[$this->name][$key]);

            return $default;
        }
        return self::$caches[$this->name][$key];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->validateKey($key);
        self::$caches[$this->name][$key] = $value;
        if ($ttl !== null) {
            if ($ttl instanceof \DateInterval) {
                $ttl = $ttl->days * 86400 + $ttl->h * 3600 + $ttl->i * 60 + $ttl->s;
            }
            self::$ttys[$this->name][$key] = time() + $ttl;
        }
        return true;
    }

    public function delete(string $key): bool
    {
        unset(self::$caches[$this->name][$key], self::$ttys[$this->name][$key]);
        return true;
    }

    public function clear(): bool
    {
        if (file_exists($this->find_dir . '/' . $this->name . '.json')) {
            unlink($this->find_dir . '/' . $this->name . '.json');
        }
        unset(self::$caches[$this->name], self::$ttys[$this->name], self::$objs[$this->name]);
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $v) {
            yield $v => $this->get($v, $default);
        }
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $k => $v) {
            if (!$this->set($k, $v, $ttl)) {
                return false;
            }
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $v) {
            if (!$this->delete($v)) {
                return false;
            }
        }
        return true;
    }

    public function has(string $key): bool
    {
        if (!isset(self::$caches[$this->name][$key])) {
            return false;
        }
        if (isset(self::$ttys[$this->name][$key])) {
            if (self::$ttys[$this->name][$key] > time()) {
                return true;
            }
            unset(self::$ttys[$this->name][$key], self::$caches[$this->name][$key]);

            return false;
        }
        return true;
    }

    private function validateKey(string $key): void
    {
        if ($key === '') {
            return;
        }
        if (strlen($key) >= 128) {
            throw new InvalidArgumentException('LightCache 键名长度不能超过 128 字节！');
        }
        // 只能包含数字、大小写字母、下划线、短横线、点、中文
        if (!preg_match('/^[\w\-.\x{4e00}-\x{9fa5}]+$/u', $key)) {
            throw new InvalidArgumentException('LightCache 键名只能包含数字、大小写字母、下划线、短横线、点、中文！');
        }
    }
}
