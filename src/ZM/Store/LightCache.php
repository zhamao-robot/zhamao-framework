<?php


namespace ZM\Store;


use Exception;
use Swoole\Table;
use ZM\Console\Console;

class LightCache
{
    /** @var Table|null */
    private static $kv_table = null;

    private static $config = [];

    public static $last_error = '';

    public static function init($config) {
        self::$config = $config;
        self::$kv_table = new Table($config["size"], $config["hash_conflict_proportion"]);
        self::$kv_table->column("value", Table::TYPE_STRING, $config["max_strlen"]);
        self::$kv_table->column("expire", Table::TYPE_INT);
        self::$kv_table->column("data_type", Table::TYPE_STRING, 12);
        $result = self::$kv_table->create();
        if ($result === true && isset($config["persistence_path"])) {
            $r = json_decode(file_get_contents($config["persistence_path"]), true);
            if ($r === null) $r = [];
            foreach ($r as $k => $v) {
                $write = self::set($k, $v);
                Console::debug("Writing LightCache: " . $k);
                if ($write === false) {
                    self::$last_error = '可能是由于 Hash 冲突过多导致动态空间无法分配内存';
                    return false;
                }
            }
        }
        if ($result === false) {
            self::$last_error = '系统内存不足，申请失败';
        }
        return $result;
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
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            if (strlen($value) >= self::$config["max_strlen"]) return false;
            $data_type = "json";
        } elseif (is_string($value)) {
            $data_type = "";
        } elseif (is_int($value)) {
            $data_type = "int";
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
            if ($v["expire"] <= time() && $v["expire"] >= 0) {
                $del[] = $k;
                continue;
            }
            $r[$k] = self::parseGet($v);
        }
        foreach ($del as $v) {
            self::unset($v);
        }
        return $r;
    }

    public static function savePersistence() {
        $r = [];
        foreach (self::$kv_table as $k => $v) {
            if ($v["expire"] === -2) {
                $r[$k] = self::parseGet($v);
            }
        }
        $r = file_put_contents(self::$config["persistence_path"], json_encode($r, 128 | 256));
        if($r === false) Console::error("Not saved, please check your \"persistence_path\"!");
    }

    private static function checkExpire($key) {
        if (($expire = self::$kv_table->get($key, "expire")) >= 0) {
            if ($expire <= time()) {
                self::$kv_table->del($key);
            }
        }
    }

    private static function parseGet($r) {
        switch ($r["data_type"]) {
            case "json":
            case "int":
                return json_decode($r["value"], true);
            case "":
            default:
                return $r["value"];
        }
    }
}
