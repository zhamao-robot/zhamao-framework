<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2019/1/5
 * Time: 4:48 PM
 */

namespace ZM\Utils;

use framework\Console;
use framework\ZMBuf;
use PDO;
use PDOException;
use SplQueue;
use Swoole\Coroutine;

class SQLPool
{
    protected $available = true;
    protected $pool;
    private $info;

    public $co_list = [];
    public $connect_cnt = 0;

    public function __construct() {
        $this->pool = new SplQueue;
        $this->info = [
            "host" => ZMBuf::globals("sql_config")["sql_host"],
            "port" => ZMBuf::globals("sql_config")["sql_port"],
            "user" => ZMBuf::globals("sql_config")["sql_username"],
            "password" => ZMBuf::globals("sql_config")["sql_password"],
            "database" => ZMBuf::globals("sql_config")["sql_database"]
        ];
        Console::debug("新建检测 MySQL 连接的计时器");
        zm_timer_tick(10000, function () {
            //Console::debug("正在检测是否有坏死的MySQL连接，当前连接池有 ".count($this->pool) . " 个连接");
            if (count($this->pool) > 0) {
                /** @var PDO $cnn */
                $cnn = $this->pool->pop();
                $this->connect_cnt -= 1;
                try {
                    $cnn->getAttribute(PDO::ATTR_SERVER_INFO);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'MySQL server has gone away') !== false) {
                        Console::info("MySQL 长连接丢失，取消连接");
                        unset($cnn);
                        return;
                    }
                }
                $this->pool->push($cnn);
                $this->connect_cnt += 1;
            }
        });
    }

    /**
     * 将利用过的连接入队
     * @param $mysql
     */
    public function put($mysql) {
        $this->pool->push($mysql);
        if (($a = array_shift($this->co_list)) !== null) {
            Coroutine::resume($a);
        }
    }

    /**
     * 获取队中的连接，如果不存在则创建新的
     * @param bool $no_new_conn
     * @return bool|mixed|PDO
     */
    public function get($no_new_conn = false) {
        if (count($this->pool) == 0 && $this->connect_cnt <= 70) {
            if ($no_new_conn) return false;
            $this->connect_cnt += 1;
            $r = $this->newConnect();
            if ($r !== false) {
                return $r;
            } else {
                $this->connect_cnt -= 1;
                return false;
            }
        } elseif (count($this->pool) > 0) {
            /** @var PDO $con */
            $con = $this->pool->pop();
            return $con;
        } elseif ($this->connect_cnt > 70) {
            $this->co_list[] = Coroutine::getuid();
            Console::warning("数据库连接过多，协程等待重复利用中...当前协程数 " . Coroutine::stats()["coroutine_num"]);
            Coroutine::suspend();
            return $this->get($no_new_conn);
        }
        return false;
    }

    public function getCount() {
        return $this->pool->count();
    }

    public function destruct() {
        // 连接池销毁, 置不可用状态, 防止新的客户端进入常驻连接池, 导致服务器无法平滑退出
        $this->available = false;
        while (!$this->pool->isEmpty()) {
            $this->pool->pop();
        }
    }

    private function newConnect() {
        //无空闲连接，创建新连接
        $dsn = "mysql:host=" . $this->info["host"] . ";dbname=" . $this->info["database"] . ";charset=utf8";
        try {
            $mysql = new PDO($dsn, $this->info["user"], $this->info["password"], array(PDO::ATTR_PERSISTENT => true));
        } catch (PDOException $e) {
            Console::error("PDO Error: " . $e->getMessage());
            return false;
        }
        Console::info("创建SQL连接中，当前有" . $this->connect_cnt . "个连接");
        return $mysql;
    }
}
