<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

/** @noinspection PhpUnused */

namespace ZM\DB;

use PDOException;
use PDOStatement;
use Swoole\Database\PDOStatementProxy;
use ZM\Console\Console;
use ZM\Exception\DbException;
use ZM\MySQL\MySQLManager;
use ZM\Store\MySQL\SqlPoolStorage;

/**
 * Class DB
 * @deprecated This will delete in 2.6 or future version, use \ZM\MySQL\MySQLManager::getConnection() instead
 */
class DB
{
    private static $table_list = [];

    /**
     * @param $db_name
     * @throws DbException
     */
    public static function initTableList($db_name)
    {
        if (!extension_loaded('mysqlnd')) {
            throw new DbException('Can not find mysqlnd PHP extension.');
        }
        $result = MySQLManager::getWrapper()->fetchAllAssociative('select TABLE_NAME from INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA=?;', [$db_name]);
        foreach ($result as $v) {
            self::$table_list[] = $v['TABLE_NAME'];
        }
    }

    /**
     * @param $table_name
     * @throws DbException
     */
    public static function table($table_name): Table
    {
        if (Table::getTableInstance($table_name) === null) {
            if (in_array($table_name, self::$table_list)) {
                return new Table($table_name);
            }
            if (SqlPoolStorage::$sql_pool !== null) {
                throw new DbException('Table ' . $table_name . ' not exist in database.');
            }
            throw new DbException('Database connection not exist or connect failed. Please check sql configuration');
        }
        return Table::getTableInstance($table_name);
    }

    /**
     * @param $line
     * @throws DbException
     */
    public static function statement($line)
    {
        self::rawQuery($line, []);
    }

    /**
     * @param $line
     * @throws DbException
     */
    public static function unprepared($line): bool
    {
        try {
            $conn = SqlPoolStorage::$sql_pool->getConnection();
            if ($conn === false) {
                SqlPoolStorage::$sql_pool->putConnection(null);
                throw new DbException('无法连接SQL！' . $line);
            }
            $result = !($conn->query($line) === false);
            SqlPoolStorage::$sql_pool->putConnection($conn);
            return $result;
        } catch (DBException $e) {
            Console::warning($e->getMessage());
            throw $e;
        }
    }

    public static function rawQuery(string $line, $params = [], $fetch_mode = ZM_DEFAULT_FETCH_MODE)
    {
        if (!is_array($params)) {
            $params = [$params];
        }
        Console::debug('MySQL: ' . $line . ' | ' . implode(', ', $params));
        try {
            if (SqlPoolStorage::$sql_pool === null) {
                throw new DbException('未连接到任何数据库！');
            }
            $conn = SqlPoolStorage::$sql_pool->getConnection();
            if ($conn === false) {
                SqlPoolStorage::$sql_pool->putConnection(null);
                throw new DbException('无法连接SQL！' . $line);
            }
            $ps = $conn->prepare($line);
            if ($ps === false) {
                SqlPoolStorage::$sql_pool->putConnection(null);
                /* @noinspection PhpUndefinedFieldInspection */
                throw new DbException('SQL语句查询错误，' . $line . '，错误信息：' . $conn->error);
            }
            if (!($ps instanceof PDOStatement) && !($ps instanceof PDOStatementProxy)) {
                var_dump($ps);
                SqlPoolStorage::$sql_pool->putConnection(null);
                throw new DbException('语句查询错误！返回的不是 PDOStatement' . $line);
            }
            if ($params == []) {
                $result = $ps->execute();
            } elseif (!is_array($params)) {
                $result = $ps->execute([$params]);
            } else {
                $result = $ps->execute($params);
            }
            if ($result !== true) {
                SqlPoolStorage::$sql_pool->putConnection(null);
                throw new DBException("语句[{$line}]错误！" . $ps->errorInfo()[2]);
                // echo json_encode(debug_backtrace(), 128 | 256);
            }
            SqlPoolStorage::$sql_pool->putConnection($conn);
            return $ps->fetchAll($fetch_mode);
        } catch (DbException $e) {
            if (mb_strpos($e->getMessage(), 'has gone away') !== false) {
                zm_sleep(0.2);
                Console::warning('Gone away of MySQL! retrying!');
                return self::rawQuery($line, $params);
            }
            Console::warning($e->getMessage());
            throw $e;
        } catch (PDOException $e) {
            if (mb_strpos($e->getMessage(), 'has gone away') !== false) {
                zm_sleep(0.2);
                Console::warning('Gone away of MySQL! retrying!');
                return self::rawQuery($line, $params);
            }
            Console::warning($e->getMessage());
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public static function isTableExists($table): bool
    {
        return in_array($table, self::$table_list);
    }
}
