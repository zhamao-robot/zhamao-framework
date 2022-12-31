<?php

declare(strict_types=1);

namespace ZM\Store\KV;

interface KVInterface
{
    /**
     * 打开一个 KV 库
     *
     * @param string $name KV 的库名称
     */
    public static function open(string $name = ''): KVInterface;

    /**
     * 返回一个 KV 键值对的数据
     *
     * @param string     $key     键名
     * @param null|mixed $default 如果不存在时返回的默认值
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 设置一个 KV 键值对的数据
     *
     * @param string $key   键名
     * @param mixed  $value 键值
     * @param int    $ttl   超时秒数（如果等于 0 代表永不超时）
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool;

    /**
     * 强制删除一个 KV 键值对数据
     *
     * @param  string $key 键名
     * @return bool   当键存在并被删除时返回 true
     */
    public function unset(string $key): bool;

    /**
     * 键值对数据是否存在
     *
     * @param string $key 键名
     */
    public function isset(string $key): bool;
}
