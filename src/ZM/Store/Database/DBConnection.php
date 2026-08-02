<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace ZM\Store\Database;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\ParameterType;
use OneBot\Driver\Interfaces\PoolInterface;

class DBConnection implements Connection
{
    private int $db_type;

    /** @var \PDO */
    private object $conn;

    private $pool_name;

    public function __construct(private array $params)
    {
        $this->db_type = $params['dbType'] ?? ZM_DB_POOL;
        if ($this->db_type === ZM_DB_POOL) {
            // 默认连接池的形式，
            logger()->debug('Constructing...');
            $this->conn = DBPool::pool($params['dbName'])->get();
            $this->pool_name = $params['dbName'];
        } elseif ($this->db_type === ZM_DB_PORTABLE) {
            $params['filename'] = DBPool::resolvePortableFilename($params['filename']);
            /** @var null|PoolInterface $pool */
            $pool = $params['pool'] ?? null;
            if ($pool instanceof PoolInterface && $pool->getFreeCount() > 0) {
                // 从连接池获取连接（此时池内有空闲连接或可新建，不会阻塞），用完归还
                $this->conn = $pool->get();
                $this->pool_name = $pool;
            } else {
                // 未启用连接池或池内连接全部被占用时，使用临时连接（用完即销毁，不进入池）
                $this->conn = new PortablePDO($params['filename'], $params['createNew'] ?? true);
            }
        }
    }

    public function __destruct()
    {
        if ($this->db_type === ZM_DB_POOL) {
            logger()->debug('DBConnection destructed');
            DBPool::pool($this->pool_name)->put($this->conn);
        } elseif ($this->db_type === ZM_DB_PORTABLE && $this->pool_name instanceof PoolInterface) {
            // 归还便携 SQLite 连接到对应的连接池，临时连接则随对象销毁释放
            $this->pool_name->put($this->conn);
        }
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

    public function getDbType(): int
    {
        return $this->db_type;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
