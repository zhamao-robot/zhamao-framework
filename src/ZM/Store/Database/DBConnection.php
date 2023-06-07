<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace ZM\Store\Database;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\ParameterType;
use ZM\Store\FileSystem;

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
            $connect_str = 'sqlite:{filename}';
            if (FileSystem::isRelativePath($params['filename'])) {
                $params['filename'] = zm_dir(config('global.data_dir') . '/db/' . $params['filename']);
                FileSystem::createDir(zm_dir(config('global.data_dir') . '/db'));
            }
            $table = [
                '{filename}' => $params['filename'],
            ];
            // 如果文件不存在则创建，但如果设置了 createNew 为 false 则不创建，不存在就直接抛出异常
            if (!file_exists($params['filename']) && ($params['createNew'] ?? true) === false) {
                throw new DBException("Database file {$params['filename']} not found!");
            }
            $connect_str = str_replace(array_keys($table), array_values($table), $connect_str);
            $this->conn = new \PDO($connect_str);
        }
    }

    public function __destruct()
    {
        if ($this->db_type === ZM_DB_POOL) {
            logger()->debug('Destructing！！！');
            DBPool::pool($this->pool_name)->put($this->conn);
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
