<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/2/25
 * Time: 下午11:11
 */

namespace ZM\Store;

use Swoole\Atomic;
use Swoole\Database\PDOPool;
use ZM\Config\ZMConfig;

class ZMBuf
{
    //读写的缓存数据，需要在worker_num = 1下才能正常使用
    /** @var mixed[] ZMBuf的data */
    private static $cache = [];
    //Swoole SQL连接池，多进程下每个进程一个连接池
    /** @var PDOPool */
    static $sql_pool = null;//保存sql连接池的类

    /** @var array Http请求uri路径根节点 */
    public static $req_mapping_node;
    /** @var array 事件注解的绑定对 */
    public static $events = [];
    /** @var Atomic[] */
    public static $atomics;
    public static $instance = [];
    public static $context_class = [];
    public static $terminal = null;

    static function get($name, $default = null) {
        return self::$cache[$name] ?? $default;
    }

    static function set($name, $value) {
        self::$cache[$name] = $value;
    }

    static function append($name, $value) {
        self::$cache[$name][] = $value;
    }

    static function appendKey($name, $key, $value) {
        self::$cache[$name][$key] = $value;
    }

    static function appendKeyInKey($name, $key, $value) {
        self::$cache[$name][$key][] = $value;
    }

    static function unsetCache($name) {
        unset(self::$cache[$name]);
    }

    static function unsetByValue($name, $vale) {
        $key = array_search($vale, self::$cache[$name]);
        array_splice(self::$cache[$name], $key, 1);
    }

    static function isset($name) {
        return isset(self::$cache[$name]);
    }

    static function array_key_exists($name, $key) {
        return isset(self::$cache[$name][$key]);
    }

    static function in_array($name, $val) {
        return in_array($val, self::$cache[$name]);
    }

    public static function resetCache() {
        self::$cache = [];
        self::$instance = [];
    }

    /**
     * 初始化atomic计数器
     */
    public static function initAtomic() {
        foreach (ZMConfig::get("global", "init_atomics") as $k => $v) {
            self::$atomics[$k] = new Atomic($v);
        }
        self::$atomics["stop_signal"] = new Atomic(0);
        self::$atomics["wait_msg_id"] = new Atomic(0);
    }

    /**
     * @param $name
     * @return Atomic|null
     */
    public static function atomic($name) {
        return self::$atomics[$name] ?? null;
    }
}
