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
use Doctrine\DBAL\Types\Type;
use Throwable;
use Traversable;
use ZM\Exception\DbException;

class MySQLWrapper
{
    /** @var Connection */
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
     */
    public function setAutoCommit(bool $auto_commit)
    {
        $this->connection->setAutoCommit($auto_commit);
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
     * @param  string      $table 表
     * @throws DbException
     */
    public function delete(string $table, array $criteria, array $types = []): int
    {
        try {
            return $this->connection->delete($table, $criteria, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param int $level Sets the transaction isolation level
     */
    public function setTransactionIsolation(int $level): int
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
     * @param  string      $table 表名
     * @throws DbException
     */
    public function update(string $table, array $data, array $criteria, array $types = []): int
    {
        try {
            return $this->connection->update($table, $data, $criteria, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param  string      $table 表名
     * @throws DbException
     */
    public function insert(string $table, array $data, array $types = []): int
    {
        try {
            return $this->connection->insert($table, $data, $types);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string $str The name to be quoted
     */
    public function quoteIdentifier(string $str): string
    {
        return $this->connection->quoteIdentifier($str);
    }

    /**
     * wrapper method
     * @param mixed                $value
     * @param null|int|string|Type $type
     */
    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->connection->quote($value, $type);
    }

    /**
     * wrapper method
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @throws DbException
     * @return array<int,array<int,mixed>>
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
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @throws DbException
     * @return array<int,array<string,mixed>>
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
     * @param string                                           $query  SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
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
     * @param string                                           $query  SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
     * @throws DbException
     * @return array<mixed,array<string,mixed>>
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
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @throws DbException
     * @return array<int,mixed>
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
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @throws DbException
     * @return Traversable<int,array<int,mixed>>
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
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @throws DbException
     * @return Traversable<int,array<string,mixed>>
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
     * @param string                                           $query  SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
     * @throws DbException
     * @return Traversable<mixed,mixed>
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
     * @param string                                           $query  SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
     * @throws DbException
     * @return Traversable<mixed,array<string,mixed>>
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
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @throws DbException
     * @return Traversable<int,mixed>
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
     * @param string                                                               $sql    SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @throws DbException
     */
    public function executeQuery(string $sql, array $params = [], array $types = [], ?QueryCacheProfile $qcp = null): MySQLStatementWrapper
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
     * @param  string                                                               $sql    SQL query
     * @param  array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param  array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     * @throws DbException
     */
    public function executeCacheQuery(string $sql, array $params, array $types, QueryCacheProfile $qcp): MySQLStatementWrapper
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
     * @param string                                                               $sql    SQL statement
     * @param array<int, mixed>|array<string, mixed>                               $params Statement parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @throws DbException
     * @return int|string  the number of affected rows
     */
    public function executeStatement(string $sql, array $params = [], array $types = [])
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
     * @param  null|string      $name name of the sequence object from which the ID should be returned
     * @return false|int|string a string representation of the last inserted ID
     */
    public function lastInsertId(?string $name = null)
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
     * @throws DbException
     */
    public function setNestTransactionsWithSavepoints(bool $nest_transactions_with_savepoints)
    {
        try {
            $this->connection->setNestTransactionsWithSavepoints($nest_transactions_with_savepoints);
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
     * @param  string      $savepoint the name of the savepoint to create
     * @throws DbException
     */
    public function createSavepoint(string $savepoint)
    {
        try {
            $this->connection->createSavepoint($savepoint);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param  string      $savepoint the name of the savepoint to release
     * @throws DbException
     */
    public function releaseSavepoint(string $savepoint)
    {
        try {
            $this->connection->releaseSavepoint($savepoint);
        } catch (Throwable $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param  string      $savepoint the name of the savepoint to rollback to
     * @throws DbException
     */
    public function rollbackSavepoint(string $savepoint)
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
