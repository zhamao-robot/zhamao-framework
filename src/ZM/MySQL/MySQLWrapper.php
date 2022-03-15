<?php

declare(strict_types=1);

/**
 * @noinspection PhpUnused
 */

namespace ZM\MySQL;

use Closure;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\ParameterType;
use Throwable;
use Traversable;
use ZM\Exception\DbException;

class MySQLWrapper
{
    private $connection;

    /**
     * MySQLWrapper constructor.
     * @throws DbException
     */
    public function __construct()
    {
        try {
            $this->connection = DriverManager::getConnection(['driverClass' => MySQLDriver::class]);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    /**
     * wrapper method
     */
    public function getDatabase(): string
    {
        return $this->connection->getDatabase();
    }

    /**
     * wrapper method
     */
    public function isAutoCommit(): bool
    {
        return $this->connection->isAutoCommit();
    }

    /**
     * wrapper method
     * @param $autoCommit
     */
    public function setAutoCommit($autoCommit)
    {
        $this->connection->setAutoCommit($autoCommit);
    }

    /**
     * wrapper method
     * @throws DbException
     * @return array|false
     */
    public function fetchAssociative(string $query, array $params = [], array $types = [])
    {
        try {
            return $this->connection->fetchAssociative($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     * @return array|false
     */
    public function fetchNumeric(string $query, array $params = [], array $types = [])
    {
        try {
            return $this->connection->fetchNumeric($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws DbException
     * @return false|mixed
     */
    public function fetchOne(string $query, array $params = [], array $types = [])
    {
        try {
            return $this->connection->fetchOne($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     */
    public function isTransactionActive(): bool
    {
        return $this->connection->isTransactionActive();
    }

    /**
     * @param $table
     * @throws DbException
     */
    public function delete($table, array $criteria, array $types = []): int
    {
        try {
            return $this->connection->delete($table, $criteria, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $level
     */
    public function setTransactionIsolation($level): int
    {
        return $this->connection->setTransactionIsolation($level);
    }

    /**
     * wrapper method
     */
    public function getTransactionIsolation(): ?int
    {
        return $this->connection->getTransactionIsolation();
    }

    /**
     * wrapper method
     * @param $table
     * @throws DbException
     */
    public function update($table, array $data, array $criteria, array $types = []): int
    {
        try {
            return $this->connection->update($table, $data, $criteria, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $table
     * @throws DbException
     */
    public function insert($table, array $data, array $types = []): int
    {
        try {
            return $this->connection->insert($table, $data, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $str
     */
    public function quoteIdentifier($str): string
    {
        return $this->connection->quoteIdentifier($str);
    }

    /**
     * wrapper method
     * @param $value
     * @param  int   $type
     * @return mixed
     */
    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->connection->quote($value, $type);
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function fetchAllNumeric(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchAllNumeric($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function fetchAllAssociative(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchAllAssociative($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function fetchAllKeyValue(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchAllKeyValue($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function fetchAllAssociativeIndexed(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchAllAssociativeIndexed($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function fetchFirstColumn(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchFirstColumn($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function iterateNumeric(string $query, array $params = [], array $types = []): Traversable
    {
        try {
            return $this->connection->iterateNumeric($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function iterateAssociative(string $query, array $params = [], array $types = []): Traversable
    {
        try {
            return $this->connection->iterateAssociative($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function iterateKeyValue(string $query, array $params = [], array $types = []): Traversable
    {
        try {
            return $this->connection->iterateKeyValue($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function iterateAssociativeIndexed(string $query, array $params = [], array $types = []): Traversable
    {
        try {
            return $this->connection->iterateAssociativeIndexed($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function iterateColumn(string $query, array $params = [], array $types = []): Traversable
    {
        try {
            return $this->connection->iterateColumn($query, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $sql
     * @param  array       $types
     * @throws DbException
     */
    public function executeQuery($sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): MySQLStatementWrapper
    {
        try {
            $query = $this->connection->executeQuery($sql, $params, $types, $qcp);
            return new MySQLStatementWrapper($query);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $sql
     * @param $params
     * @param $types
     * @throws DbException
     */
    public function executeCacheQuery($sql, $params, $types, QueryCacheProfile $qcp): MySQLStatementWrapper
    {
        try {
            $query = $this->connection->executeCacheQuery($sql, $params, $types, $qcp);
            return new MySQLStatementWrapper($query);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $sql
     * @throws DbException
     */
    public function executeStatement($sql, array $params = [], array $types = []): int
    {
        try {
            return $this->connection->executeStatement($sql, $params, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     */
    public function getTransactionNestingLevel(): int
    {
        return $this->connection->getTransactionNestingLevel();
    }

    /**
     * wrapper method
     * @param null $name
     */
    public function lastInsertId($name = null): string
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * overwrite method to $this->connection->transactional()
     * @throws DbException
     * @return mixed
     */
    public function transactional(Closure $func)
    {
        $this->beginTransaction();
        try {
            $res = $func($this);
            $this->commit();
            return $res;
        } catch (Throwable $e) {
            $this->rollBack();
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $nestTransactionsWithSavepoints
     * @throws DbException
     */
    public function setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints)
    {
        try {
            $this->connection->setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     */
    public function getNestTransactionsWithSavepoints(): bool
    {
        return $this->connection->getNestTransactionsWithSavepoints();
    }

    /**
     * wrapper method
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function commit(): bool
    {
        try {
            return $this->connection->commit();
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function rollBack(): bool
    {
        try {
            return $this->connection->rollBack();
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $savepoint
     * @throws DbException
     */
    public function createSavepoint($savepoint)
    {
        try {
            $this->connection->createSavepoint($savepoint);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $savepoint
     * @throws DbException
     */
    public function releaseSavepoint($savepoint)
    {
        try {
            $this->connection->releaseSavepoint($savepoint);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param $savepoint
     * @throws DbException
     */
    public function rollbackSavepoint($savepoint)
    {
        try {
            $this->connection->rollbackSavepoint($savepoint);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function setRollbackOnly()
    {
        try {
            $this->connection->setRollbackOnly();
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DbException
     */
    public function isRollbackOnly(): bool
    {
        try {
            return $this->connection->isRollbackOnly();
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * overwrite method to $this->connection->createQueryBuilder
     */
    public function createQueryBuilder(): MySQLQueryBuilder
    {
        return new MySQLQueryBuilder($this);
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
