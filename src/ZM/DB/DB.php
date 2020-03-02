<?php


namespace ZM\DB;


use framework\Console;
use framework\ZMBuf;
use Swoole\Coroutine;
use Swoole\Coroutine\MySQL\Statement;
use ZM\Exception\DbException;

class DB
{
    private static $table_list = [];

    public static function initTableList() {
        $result = self::rawQuery("select TABLE_NAME from INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA='" . ZMBuf::globals("sql_config")["sql_database"] . "';", []);
        foreach ($result as $v) {
            self::$table_list[] = $v['TABLE_NAME'];
        }
    }

    /**
     * @param $table_name
     * @param bool $enable_cache
     * @return Table
     * @throws DbException
     */
    public static function table($table_name, $enable_cache = null) {
        if (Table::getTableInstance($table_name) === null) {
            if (in_array($table_name, self::$table_list))
                return new Table($table_name, $enable_cache ?? ZMBuf::globals("sql_config")["sql_enable_cache"]);
            elseif(ZMBuf::$sql_pool !== null){
                throw new DbException("Table " . $table_name . " not exist in database.");
            } else {
                throw new DbException("Database connection not exist or connect failed. Please check sql configuration");
            }
        }
        return Table::getTableInstance($table_name);
    }

    public static function statement($line) {
        self::rawQuery($line, []);
    }

    public static function unprepared($line) {
        if (ZMBuf::get("sql_log") === true) {
            $starttime = microtime(true);
        }
        try {
            $conn = ZMBuf::$sql_pool->get();
            if ($conn === false) {
                throw new DbException("无法连接SQL！" . $line);
            }
            $result = $conn->query($line) === false ? false : ($conn->errno != 0 ? false : true);
            ZMBuf::$sql_pool->put($conn);
            return $result;
        } catch (DBException $e) {
            if (ZMBuf::get("sql_log") === true) {
                $log =
                    "[" . date("Y-m-d H:i:s") .
                    " " . round(microtime(true) - $starttime, 5) .
                    "] " . $line . " (Error:" . $e->getMessage() . ")\n";
                Coroutine::writeFile(CRASH_DIR . "sql.log", $log, FILE_APPEND);
            }
            Console::error($e->getMessage());
            return false;
        }
    }

    public static function rawQuery(string $line, $params) {
        if (ZMBuf::get("sql_log") === true) {
            $starttime = microtime(true);
        }
        try {
            $conn = ZMBuf::$sql_pool->get();
            if ($conn === false) {
                throw new DbException("无法连接SQL！" . $line);
            }
            $ps = $conn->prepare($line);
            if ($ps === false) {
                $conn->close();
                ZMBuf::$sql_pool->connect_cnt -= 1;
                throw new DbException("SQL语句查询错误，" . $line . "，错误信息：" . $conn->error);
            } else {
                if (!($ps instanceof Statement)) {
                    throw new DbException("语句查询错误！" . $line);
                }
                if ($params == []) $result = $ps->execute();
                elseif (!is_array($params)) {
                    $result = $ps->execute([$params]);
                } else $result = $ps->execute($params);
                ZMBuf::$sql_pool->put($conn);
                if ($ps->errno != 0) {
                    throw new DBException("语句[$line]错误！" . $ps->error);
                    //echo json_encode(debug_backtrace(), 128 | 256);
                }
                if (ZMBuf::get("sql_log") === true) {
                    $log =
                        "[" . date("Y-m-d H:i:s") .
                        " " . round(microtime(true) - $starttime, 4) .
                        "] " . $line . " " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n";
                    Coroutine::writeFile(CRASH_DIR . "sql.log", $log, FILE_APPEND);
                }
                return $result;
            }
        } catch (DBException $e) {
            if (ZMBuf::get("sql_log") === true) {
                $log =
                    "[" . date("Y-m-d H:i:s") .
                    " " . round(microtime(true) - $starttime, 4) .
                    "] " . $line . " " . json_encode($params, JSON_UNESCAPED_UNICODE) . " (Error:" . $e->getMessage() . ")\n";
                Coroutine::writeFile(CRASH_DIR . "sql.log", $log, FILE_APPEND);
            }
            Console::error($e->getMessage());
            return false;
        }
    }
}