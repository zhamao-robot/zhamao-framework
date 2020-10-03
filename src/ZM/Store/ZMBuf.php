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
    //Swoole SQL连接池，多进程下每个进程一个连接池
    /** @var PDOPool */
    static $sql_pool = null;//保存sql连接池的类

    /** @var array 事件注解的绑定对 */
    public static $events = [];

    // 下面的有用，上面的没用了
    /** @var Atomic[] */
    public static $atomics;
    public static $instance = [];
    public static $context_class = [];
    public static $terminal = null;

    /**
     * 初始化atomic计数器
     */
    public static function initAtomic() {
        foreach (ZMConfig::get("global", "init_atomics") as $k => $v) {
            self::$atomics[$k] = new Atomic($v);
        }
        self::$atomics["stop_signal"] = new Atomic(0);
        self::$atomics["wait_msg_id"] = new Atomic(0);
        for($i = 0; $i < 10; ++$i) {
            self::$atomics["_tmp_".$i] = new Atomic(0);
        }
    }

    /**
     * @param $name
     * @return Atomic|null
     */
    public static function atomic($name) {
        return self::$atomics[$name] ?? null;
    }
}
