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
use Swoole\Coroutine\Mysql;

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
        $mysql = new Mysql();
        $dsn = "mysql:host=" . $this->info["host"] . ";dbname=" . $this->info["database"];
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
