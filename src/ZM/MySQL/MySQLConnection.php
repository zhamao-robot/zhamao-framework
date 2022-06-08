<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace ZM\MySQL;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use Swoole\Database\PDOProxy;
use ZM\Exception\DbException;
use ZM\Store\MySQL\SqlPoolStorage;

class MySQLConnection implements Connection
{
    /** @var PDO|PDOProxy */
    private $conn;

    public function __construct()
    {
        logger()->debug('Constructing...');
        $this->conn = SqlPoolStorage::$sql_pool->getConnection();
    }

    public function __destruct()
    {
        logger()->debug('Destructing！！！');
        SqlPoolStorage::$sql_pool->putConnection($this->conn);
    }

    /**
     * @param  mixed       $sql
     * @param  mixed       $options
     * @throws DbException
     */
    public function prepare($sql, $options = [])
    {
        try {
            logger()->debug('Running SQL prepare: ' . $sql);
            $statement = $this->conn->prepare($sql, $options);
            assert($statement !== false);
        } catch (PDOException $exception) {
            throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return new MySQLStatement($statement);
    }

    /**
     * @throws DbException
     */
    public function query(...$args)
    {
        try {
            $statement = $this->conn->query(...$args);
            assert($statement !== false);
        } catch (PDOException $exception) {
            throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return new MySQLStatement($statement);
    }

    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->conn->quote($value, $type);
    }

    /**
     * @param  mixed       $sql
     * @throws DbException
     */
    public function exec($sql)
    {
        try {
            logger()->debug('Running SQL exec: ' . $sql);
            $statement = $this->conn->exec($sql);
            assert($statement !== false);
            return $statement;
        } catch (PDOException $exception) {
            throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @param  null|mixed  $name
     * @throws DbException
     */
    public function lastInsertId($name = null)
    {
        try {
            return $name === null ? $this->conn->lastInsertId() : $this->conn->lastInsertId($name);
        } catch (PDOException $exception) {
            throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollBack()
    {
        return $this->conn->rollBack();
    }

    public function errorCode()
    {
        return $this->conn->errorCode();
    }

    public function errorInfo()
    {
        return $this->conn->errorInfo();
    }
}
