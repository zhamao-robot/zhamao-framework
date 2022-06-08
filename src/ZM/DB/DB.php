<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

/** @noinspection PhpUnused */

namespace ZM\DB;

use PDOException;
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
     * @param  string      $db_name 数据库名称
     * @throws DbException
     */
    public static function initTableList(string $db_name)
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
     * @param  string      $table_name 表名
     * @throws DbException
     * @return Table       返回表对象
     */
    public static function table(string $table_name): Table
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
     * @param  string      $line SQL语句
     * @throws DbException
     */
    public static function statement(string $line)
    {
        self::rawQuery($line, []);
    }

    /**
     * @param  string $line SQL语句
     * @return bool   返回查询是否成功的结果
     */
    public static function unprepared(string $line): bool
    {
        $conn = SqlPoolStorage::$sql_pool->getConnection();
        $result = !($conn->query($line) === false);
        SqlPoolStorage::$sql_pool->putConnection($conn);
        return $result;
    }

    /**
     * @param  string      $line       SQL语句
     * @param  array       $params     查询参数
     * @param  int         $fetch_mode fetch规则
     * @throws DbException
     * @return array|false 返回结果集或false
     */
    public static function rawQuery(string $line, array $params = [], int $fetch_mode = ZM_DEFAULT_FETCH_MODE)
    {
        if (!is_array($params)) {
            $params = [$params];
        }
        logger()->debug('MySQL: ' . $line . ' | ' . implode(', ', $params));
        try {
            if (SqlPoolStorage::$sql_pool === null) {
                throw new DbException('未连接到任何数据库！');
            }
            $conn = SqlPoolStorage::$sql_pool->getConnection();
            $ps = $conn->prepare($line);
            if ($ps === false) {
                SqlPoolStorage::$sql_pool->putConnection(null);
                throw new DbException('SQL语句查询错误，' . $line . '，错误信息：' . $conn->errorInfo()[2]);
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
                zm_sleep();
                logger()->warning('Gone away of MySQL! retrying!');
                return self::rawQuery($line, $params);
            }
            logger()->warning($e->getMessage());
            throw $e;
        } catch (PDOException $e) {
            if (mb_strpos($e->getMessage(), 'has gone away') !== false) {
                zm_sleep();
                logger()->warning('Gone away of MySQL! retrying!');
                return self::rawQuery($line, $params);
            }
            logger()->warning($e->getMessage());
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public static function isTableExists($table): bool
    {
        return in_array($table, self::$table_list);
    }
}
