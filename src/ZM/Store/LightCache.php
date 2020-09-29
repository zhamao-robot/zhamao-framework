<?php


namespace ZM\Store;


use Exception;
use Swoole\Table;

class LightCache
{
    /** @var Table|null */
    private static $kv_table = null;

    private static $config = [];

    public static function init($config) {
        self::$config = $config;
        self::$kv_table = new Table($config["size"], $config["hash_conflict_proportion"]);
        self::$kv_table->column("value", Table::TYPE_STRING, $config["max_strlen"]);
        self::$kv_table->column("expire", Table::TYPE_INT);
        self::$kv_table->column("data_type", Table::TYPE_STRING, 12);
        return self::$kv_table->create();
    }

    /**
     * @param string $key
     * @return null|string
     * @throws Exception
     */
    public static function get(string $key) {
        if (self::$kv_table === null) throw new Exception("not initialized LightCache");
        self::checkExpire($key);
        $r = self::$kv_table->get($key);
        return $r === false ? null : self::parseGet($r);
    }

    /**
     * @param string $key
     * @return mixed|null
     * @throws Exception
     */
    public static function getExpire(string $key) {
        if (self::$kv_table === null) throw new Exception("not initialized LightCache");
        self::checkExpire($key);
        $r = self::$kv_table->get($key, "expire");
        return $r === false ? null : $r - time();
    }

    /**
     * @param string $key
     * @param string|array|int $value
     * @param int $expire
     * @return mixed
     * @throws Exception
     */
    public static function set(string $key, $value, int $expire = -1) {
        if (self::$kv_table === null) throw new Exception("not initialized LightCache");
        if (is_array($value) || is_int($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            if (strlen($value) >= self::$config["max_strlen"]) return false;
            $data_type = "json";
        } elseif (is_string($value)) {
            $data_type = "";
        } else {
            throw new Exception("Only can set string, array and int");
        }
        try {
            return self::$kv_table->set($key, [
                "value" => $value,
                "expire" => $expire != -1 ? $expire + time() : -1,
                "data_type" => $data_type
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getMemoryUsage() {
        return self::$kv_table->getMemorySize();
    }

    /**
     * @param string $key
     * @return bool
     * @throws Exception
     */
    public static function isset(string $key) {
        return self::get($key) !== null;
    }

    public static function unset(string $key) {
        return self::$kv_table->del($key);
    }

    public static function getAll() {
        $r = [];
        $del = [];
        foreach (self::$kv_table as $k => $v) {
            if ($v["expire"] <= time()) {
                $del[]=$k;
                continue;
            }
            $r[$k] = self::parseGet($v);
        }
        foreach($del as $v) {
            self::unset($v);
        }
        return $r;
    }

    private static function checkExpire($key) {
        if (($expire = self::$kv_table->get($key, "expire")) !== -1) {
            if ($expire <= time()) {
                self::$kv_table->del($key);
            }
        }
    }

    private static function parseGet($r) {
        switch ($r["data_type"]) {
            case "json":
                return json_decode($r["value"], true);
            case "":
            default:
                return $r["value"];
        }
    }
}
