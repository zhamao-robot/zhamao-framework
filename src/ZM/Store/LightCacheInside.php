<?php

declare(strict_types=1);

namespace ZM\Store;

use Exception;
use Swoole\Table;
use ZM\Exception\LightCacheException;
use ZM\Exception\ZMException;

class LightCacheInside
{
    /** @var null|Table[] */
    private static $kv_table = [];

    public static function init(): bool
    {
        try {
            $size = ZMConfig::get('global', 'runtime')['inside_table_size'] ?? 65536;
            self::createTable('wait_api', 3, $size);
            self::createTable('connect', 3, 64);        // 用于存单机器人模式下的机器人fd的
            self::createTable('static_route', 64, 256); // 用于存储
            self::createTable('light_array', 8, 512, 0.6);
        } catch (ZMException $e) {
            return false;
        }    // 用于存协程等待的状态内容的
        // self::createTable("worker_start", 2, 1024);//用于存启动服务器时的状态的
        return true;
    }

    /**
     * @return null|mixed
     */
    public static function get(string $table, string $key)
    {
        $r = self::$kv_table[$table]->get($key);
        return $r === false ? null : json_decode($r['value'], true);
    }

    /**
     * @param array|int|string $value
     */
    public static function set(string $table, string $key, $value): bool
    {
        try {
            return self::$kv_table[$table]->set($key, [
                'value' => json_encode($value, 256),
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function unset(string $table, string $key)
    {
        return self::$kv_table[$table]->del($key);
    }

    /**
     * @param  float|int   $conflict_proportion
     * @throws ZMException
     */
    private static function createTable(string $name, int $size, int $str_size, $conflict_proportion = 0)
    {
        self::$kv_table[$name] = new Table($size, $conflict_proportion);
        self::$kv_table[$name]->column('value', Table::TYPE_STRING, $str_size);
        $r = self::$kv_table[$name]->create();
        if ($r === false) {
            throw new LightCacheException('E00050', '内存不足，创建静态表失败！');
        }
    }
}
