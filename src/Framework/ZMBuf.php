<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/2/25
 * Time: 下午11:11
 */

namespace Framework;

use Swoole\Atomic;
use swoole_atomic;
use ZM\Annotation\MappingNode;
use ZM\connection\WSConnection;
use ZM\Utils\Scheduler;
use ZM\Utils\SQLPool;

class ZMBuf
{
    //读写的缓存数据，需要在worker_num = 1下才能正常使用
    /** @var mixed[] ZMBuf的data */
    private static $cache = [];
    /** @var WSConnection[] */
    static $connect = [];//储存连接实例的数组
    //Scheduler计划任务连接实例，只可以在单worker_num时使用
    /** @var Scheduler|null */
    static $scheduler = null;

    //Swoole SQL连接池，多进程下每个进程一个连接池
    /** @var SQLPool */
    static $sql_pool = null;//保存sql连接池的类

    //只读的数据，可以在多worker_num下使用
    /** @var null|\Framework\GlobalConfig */
    static $globals = null;

    // swoole server操作对象，每个进程均分配
    /** @var swoole_websocket_server $server */
    static $server;
    /** @var MappingNode Http请求uri路径根节点 */
    public static $req_mapping_node;
    /** @var mixed TimeNLP初始化后的对象，每个进程均可初始化 */
    public static $time_nlp;
    /** @var string[] $custom_connection_class */
    public static $custom_connection_class = [];//保存自定义的ws connection连接类型的

    // Atomic：可跨进程读写的原子计数，任何地方均可使用
    /** @var null|swoole_atomic */
    static $info_level = null;//保存log等级的原子计数
    /** @var swoole_atomic $reload_time */

    public static $events = [];
    /** @var Atomic[] */
    public static $atomics;

    static function get($name, $default = null) { return self::$cache[$name] ?? $default; }

    static function set($name, $value) { self::$cache[$name] = $value; }

    static function append($name, $value) { self::$cache[$name][] = $value; }

    static function appendKey($name, $key, $value) { self::$cache[$name][$key] = $value; }

    static function appendKeyInKey($name, $key, $value) { self::$cache[$name][$key][] = $value; }

    static function unsetCache($name) { unset(self::$cache[$name]); }

    static function unsetByValue($name, $vale) {
        $key = array_search($vale, self::$cache[$name]);
        array_splice(self::$cache[$name], $key, 1);
    }

    static function isset($name) { return isset(self::$cache[$name]); }

    static function array_key_exists($name, $key) { return isset(self::$cache[$name][$key]); }

    static function in_array($name, $val) { return in_array($val, self::$cache[$name]); }

    static function globals($key) { return self::$globals->get($key); }

    public static function resetCache() {
        self::$cache = [];
        self::$connect = [];
        self::$time_nlp = null;
    }

    /**
     * 初始化atomic计数器
     */
    public static function initAtomic() {
        foreach(ZMBuf::globals("init_atomics") as $k => $v) {
            self::$atomics[$k] = new Atomic($v);
        }
    }
}