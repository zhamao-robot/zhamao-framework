<?php

declare(strict_types=1);

namespace ZM\Store\Database;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;

class DBWrapper
{
    private Connection $connection;

    /**
     * DBWrapper constructor.
     * @throws DBException
     */
    public function __construct(string $name, array $options = [])
    {
        // 初始化配置
        $db_type = $options['dbType'] ?? ZM_DB_POOL;
        try {
            if ($db_type === ZM_DB_POOL) {
                // 设置 dbName
                $options['dbName'] = $name;
                // pool 为连接池格式
                $db_list = config()->get('global.database');
                if (isset($db_list[$name]) || (is_countable($db_list) ? count($db_list) : 0) === 1) {
                    if ($name === '') {
                        $name = array_key_first($db_list);
                    }
                    $this->connection = DriverManager::getConnection(array_merge(
                        ['driverClass' => $this->getConnectionClass($db_list[$name]['type']), 'dbName' => $name],
                        $options
                    ));
                } else {
                    throw new DBException('Cannot find database config named "' . $name . '" !');
                }
            } elseif ($db_type === ZM_DB_PORTABLE) {
                // portable 为sqlite单文件模式
                $this->connection = DriverManager::getConnection(array_merge(
                    ['driverClass' => SQLiteDriver::class, 'filename' => $name],
                    $options
                ));
            }
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function __destruct()
    {
        $this->connection->close();
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
     * @throws DBException
     */
    public function fetchAssociative(string $query, array $params = [], array $types = []): array|false
    {
        try {
            return $this->connection->fetchAssociative($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), intval($e->getCode()), $e);
        }
    }

    /**
     * wrapper method
     * @throws DBException
     */
    public function fetchNumeric(string $query, array $params = [], array $types = []): array|false
    {
        try {
            return $this->connection->fetchNumeric($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return false|mixed
     * @throws DBException
     */
    public function fetchOne(string $query, array $params = [], array $types = [])
    {
        try {
            return $this->connection->fetchOne($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
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
     * @throws DBException
     */
    public function delete(string $table, array $criteria, array $types = []): int
    {
        try {
            return $this->connection->delete($table, $criteria, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
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
     * @throws DBException
     */
    public function update(string $table, array $data, array $criteria, array $types = []): int
    {
        try {
            return $this->connection->update($table, $data, $criteria, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param  string      $table 表名
     * @throws DBException
     */
    public function insert(string $table, array $data, array $types = []): int
    {
        try {
            return $this->connection->insert($table, $data, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
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
     * @param null|int|string|Type $type
     */
    public function quote(mixed $value, $type = ParameterType::STRING)
    {
        return $this->connection->quote($value, $type);
    }

    /**
     * wrapper method
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @return array<int,array<int,mixed>>
     * @throws DBException
     */
    public function fetchAllNumeric(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchAllNumeric($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @return array<int,array<string,mixed>>
     * @throws DBException
     */
    public function fetchAllAssociative(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchAllAssociative($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                           $query  SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
     * @throws DBException
     */
    public function fetchAllKeyValue(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchAllKeyValue($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                           $query  SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
     * @return array<mixed,array<string,mixed>>
     * @throws DBException
     */
    public function fetchAllAssociativeIndexed(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchAllAssociativeIndexed($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @return array<int,mixed>
     * @throws DBException
     */
    public function fetchFirstColumn(string $query, array $params = [], array $types = []): array
    {
        try {
            return $this->connection->fetchFirstColumn($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @return \Traversable<int,array<int,mixed>>
     * @throws DBException
     */
    public function iterateNumeric(string $query, array $params = [], array $types = []): \Traversable
    {
        try {
            return $this->connection->iterateNumeric($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @return \Traversable<int,array<string,mixed>>
     * @throws DBException
     */
    public function iterateAssociative(string $query, array $params = [], array $types = []): \Traversable
    {
        try {
            return $this->connection->iterateAssociative($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                           $query  SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
     * @return \Traversable<mixed,mixed>
     * @throws DBException
     */
    public function iterateKeyValue(string $query, array $params = [], array $types = []): \Traversable
    {
        try {
            return $this->connection->iterateKeyValue($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                           $query  SQL query
     * @param array<int, mixed>|array<string, mixed>           $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
     * @return \Traversable<mixed,array<string,mixed>>
     * @throws DBException
     */
    public function iterateAssociativeIndexed(string $query, array $params = [], array $types = []): \Traversable
    {
        try {
            return $this->connection->iterateAssociativeIndexed($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                                               $query  SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @return \Traversable<int,mixed>
     * @throws DBException
     */
    public function iterateColumn(string $query, array $params = [], array $types = []): \Traversable
    {
        try {
            return $this->connection->iterateColumn($query, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                                               $sql    SQL query
     * @param array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @throws DBException
     */
    public function executeQuery(string $sql, array $params = [], array $types = [], ?QueryCacheProfile $qcp = null): DBStatementWrapper
    {
        try {
            $query = $this->connection->executeQuery($sql, $params, $types, $qcp);
            return new DBStatementWrapper($query);
        } catch (\Throwable $e) {
            throw $e;
            // throw new DBException($e->getMessage(), intval($e->getCode()), $e);
        }
    }

    /**
     * wrapper method
     * @param  string                                                               $sql    SQL query
     * @param  array<int, mixed>|array<string, mixed>                               $params Query parameters
     * @param  array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     * @throws DBException
     */
    public function executeCacheQuery(string $sql, array $params, array $types, QueryCacheProfile $qcp): DBStatementWrapper
    {
        try {
            $query = $this->connection->executeCacheQuery($sql, $params, $types, $qcp);
            return new DBStatementWrapper($query);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param string                                                               $sql    SQL statement
     * @param array<int, mixed>|array<string, mixed>                               $params Statement parameters
     * @param array<int, null|int|string|Type>|array<string, null|int|string|Type> $types  Parameter types
     *
     * @return int|string  the number of affected rows
     * @throws DBException
     */
    public function executeStatement(string $sql, array $params = [], array $types = []): int|string
    {
        try {
            return $this->connection->executeStatement($sql, $params, $types);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
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
    public function lastInsertId(?string $name = null): false|int|string
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * overwrite method to $this->connection->transactional()
     * @return mixed
     * @throws DBException
     */
    public function transactional(\Closure $func)
    {
        $this->beginTransaction();
        try {
            $res = $func($this);
            $this->commit();
            return $res;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DBException
     */
    public function setNestTransactionsWithSavepoints(bool $nest_transactions_with_savepoints)
    {
        try {
            $this->connection->setNestTransactionsWithSavepoints($nest_transactions_with_savepoints);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
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
     * @throws DBException
     */
    public function commit(): bool
    {
        try {
            return $this->connection->commit();
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DBException
     */
    public function rollBack(): bool
    {
        try {
            return $this->connection->rollBack();
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param  string      $savepoint the name of the savepoint to create
     * @throws DBException
     */
    public function createSavepoint(string $savepoint)
    {
        try {
            $this->connection->createSavepoint($savepoint);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param  string      $savepoint the name of the savepoint to release
     * @throws DBException
     */
    public function releaseSavepoint(string $savepoint)
    {
        try {
            $this->connection->releaseSavepoint($savepoint);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @param  string      $savepoint the name of the savepoint to rollback to
     * @throws DBException
     */
    public function rollbackSavepoint(string $savepoint)
    {
        try {
            $this->connection->rollbackSavepoint($savepoint);
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DBException
     */
    public function setRollbackOnly()
    {
        try {
            $this->connection->setRollbackOnly();
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws DBException
     */
    public function isRollbackOnly(): bool
    {
        try {
            return $this->connection->isRollbackOnly();
        } catch (\Throwable $e) {
            throw new DBException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * overwrite method to $this->connection->createQueryBuilder
     */
    public function createQueryBuilder(): DBQueryBuilder
    {
        return new DBQueryBuilder($this);
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @throws DBException
     */
    private function getConnectionClass(string $type): string
    {
        return match ($type) {
            'mysql' => MySQLDriver::class,
            'sqlite' => SQLiteDriver::class,
            'pgsql', 'postgres' => PgSQLDriver::class,
            default => throw new DBException('Unknown database type: ' . $type),
        };
    }
}
