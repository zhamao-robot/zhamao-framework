<?php


namespace ZM\Store;


use Exception;
use Swoole\Table;

class LightCacheInside
{
    /** @var Table[]|null */
    private static $kv_table = [];

    public static $last_error = '';

    public static function init() {
        self::$kv_table["wait_api"] = new Table(2, 0);
        self::$kv_table["wait_api"]->column("value", Table::TYPE_STRING, 65536);
        $result = self::$kv_table["wait_api"]->create();
        if ($result === false) {
            self::$last_error = '系统内存不足，申请失败';
            return $result;
        }
        return $result;
    }

    public static function get(string $table, string $key) {
        if (!isset(self::$kv_table[$table])) throw new Exception("not initialized LightCache");
        $r = self::$kv_table[$table]->get($key);
        return $r === false ? null : json_decode($r["value"], true);
    }

    /**
     * @param string $table
     * @param string $key
     * @param string|array|int $value
     * @return mixed
     * @throws Exception
     */
    public static function set(string $table, string $key, $value) {
        if (self::$kv_table === null) throw new Exception("not initialized LightCache");
        try {
            return self::$kv_table[$table]->set($key, [
                "value" => json_encode($value, 256)
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function unset(string $table, string $key) {
        return self::$kv_table[$table]->del($key);
    }
}
