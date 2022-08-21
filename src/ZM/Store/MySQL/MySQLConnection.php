<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace ZM\Store\MySQL;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use Swoole\Database\PDOProxy;

class MySQLConnection implements Connection
{
    /** @var PDO|PDOProxy */
    private $conn;

    private $pool_name;

    public function __construct($params)
    {
        logger()->debug('Constructing...');
        $this->conn = MySQLPool::pool($params['dbName'])->get();
        $this->pool_name = $params['dbName'];
    }

    public function __destruct()
    {
        logger()->debug('Destructing！！！');
        MySQLPool::pool($this->pool_name)->put($this->conn);
    }

    /**
     * @param mixed $sql
     * @param mixed $options
     */
    public function prepare($sql, $options = [])
    {
        try {
            logger()->debug('Running SQL prepare: ' . $sql);
            $statement = $this->conn->prepare($sql, $options);
            assert($statement !== false);
        } catch (PDOException $exception) {
            throw new MySQLException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return new MySQLStatement($statement);
    }

    /**
     * @throws MySQLException
     */
    public function query(...$args)
    {
        try {
            $statement = $this->conn->query(...$args);
            assert($statement !== false);
        } catch (PDOException $exception) {
            throw new MySQLException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return new MySQLStatement($statement);
    }

    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->conn->quote($value, $type);
    }

    /**
     * @param  mixed          $sql
     * @throws MySQLException
     */
    public function exec($sql)
    {
        try {
            logger()->debug('Running SQL exec: ' . $sql);
            $statement = $this->conn->exec($sql);
            assert($statement !== false);
            return $statement;
        } catch (PDOException $exception) {
            throw new MySQLException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @param  null|mixed     $name
     * @throws MySQLException
     */
    public function lastInsertId($name = null)
    {
        try {
            return $name === null ? $this->conn->lastInsertId() : $this->conn->lastInsertId($name);
        } catch (PDOException $exception) {
            throw new MySQLException($exception->getMessage(), $exception->getCode(), $exception);
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
