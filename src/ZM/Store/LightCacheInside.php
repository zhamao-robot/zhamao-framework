<?php


namespace ZM\Store;


use Exception;
use Swoole\Table;
use ZM\Exception\ZMException;

class LightCacheInside
{
    /** @var Table[]|null */
    private static $kv_table = [];

    public static $last_error = '';

    public static function init() {
        self::createTable("wait_api", 3, 65536);    //用于存协程等待的状态内容的
        self::createTable("connect", 3, 64);        //用于存单机器人模式下的机器人fd的
        //self::createTable("worker_start", 2, 1024);//用于存启动服务器时的状态的
        return true;
    }

    /**
     * @param string $table
     * @param string $key
     * @return mixed|null
     * @throws ZMException
     */
    public static function get(string $table, string $key) {
        if (!isset(self::$kv_table[$table])) throw new ZMException("not initialized LightCache");
        $r = self::$kv_table[$table]->get($key);
        return $r === false ? null : json_decode($r["value"], true);
    }

    /**
     * @param string $table
     * @param string $key
     * @param string|array|int $value
     * @return mixed
     * @throws ZMException
     */
    public static function set(string $table, string $key, $value) {
        if (self::$kv_table === null) throw new ZMException("not initialized LightCache");
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

    /**
     * @param $name
     * @param $size
     * @param $str_size
     * @throws ZMException
     */
    private static function createTable($name, $size, $str_size) {
        self::$kv_table[$name] = new Table($size, 0);
        self::$kv_table[$name]->column("value", Table::TYPE_STRING, $str_size);
        $r = self::$kv_table[$name]->create();
        if ($r === false) throw new ZMException("内存不足，创建静态表失败！");
    }
}
