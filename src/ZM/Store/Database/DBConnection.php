<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace ZM\Store\Database;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\ParameterType;

class DBConnection implements Connection
{
    /** @var \PDO */
    private $conn;

    private $pool_name;

    public function __construct($params)
    {
        logger()->debug('Constructing...');
        $this->conn = DBPool::pool($params['dbName'])->get();
        $this->pool_name = $params['dbName'];
    }

    public function __destruct()
    {
        logger()->debug('Destructing！！！');
        DBPool::pool($this->pool_name)->put($this->conn);
    }

    /**
     * @param  mixed       $sql
     * @param  mixed       $options
     * @throws DBException
     */
    public function prepare($sql, $options = [])
    {
        try {
            logger()->debug('Running SQL prepare: ' . $sql);
            $statement = $this->conn->prepare($sql, $options);
            assert($statement !== false);
        } catch (\PDOException $exception) {
            throw new DBException($exception->getMessage(), 0, $exception);
        }
        return new DBStatement($statement);
    }

    /**
     * @throws DBException
     */
    public function query(...$args)
    {
        try {
            $statement = $this->conn->query(...$args);
            assert($statement !== false);
        } catch (\PDOException $exception) {
            throw new DBException($exception->getMessage(), 0, $exception);
        }
        return new DBStatement($statement);
    }

    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->conn->quote($value, $type);
    }

    /**
     * @param  mixed       $sql
     * @throws DBException
     */
    public function exec($sql)
    {
        try {
            logger()->debug('Running SQL exec: ' . $sql);
            $statement = $this->conn->exec($sql);
            assert($statement !== false);
            return $statement;
        } catch (\PDOException $exception) {
            throw new DBException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param  null|mixed  $name
     * @throws DBException
     */
    public function lastInsertId($name = null)
    {
        try {
            return $name === null ? $this->conn->lastInsertId() : $this->conn->lastInsertId($name);
        } catch (\PDOException $exception) {
            throw new DBException($exception->getMessage(), 0, $exception);
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

    /**
     * @return mixed
     */
    public function getPoolName()
    {
        return $this->pool_name;
    }
}
