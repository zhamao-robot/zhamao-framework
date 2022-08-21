<?php

/**
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace ZM\Store\MySQL;

use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Throwable;
use Traversable;

class MySQLStatementWrapper
{
    public $stmt;

    public function __construct(?Result $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * 获取结果的迭代器
     * wrapper method
     * @return ResultStatement
     */
    public function getIterator()
    {
        return $this->stmt->getIterator();
    }

    /**
     * 获取列数
     * wrapper method
     * @return int
     */
    public function columnCount()
    {
        return $this->stmt->columnCount();
    }

    /**
     * wrapper method
     * @throws MySQLException
     * @return array|false|mixed
     */
    public function fetchNumeric()
    {
        try {
            return $this->stmt->fetchNumeric();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     * @return array|false|mixed
     */
    public function fetchAssociative()
    {
        try {
            return $this->stmt->fetchAssociative();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     * @return false|mixed
     */
    public function fetchOne()
    {
        try {
            return $this->stmt->fetchOne();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function fetchAllNumeric(): array
    {
        try {
            return $this->stmt->fetchAllNumeric();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function fetchAllAssociative(): array
    {
        try {
            return $this->stmt->fetchAllAssociative();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function fetchAllKeyValue(): array
    {
        try {
            return $this->stmt->fetchAllKeyValue();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function fetchAllAssociativeIndexed(): array
    {
        try {
            return $this->stmt->fetchAllAssociativeIndexed();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function fetchFirstColumn(): array
    {
        try {
            return $this->stmt->fetchFirstColumn();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function iterateNumeric(): Traversable
    {
        try {
            return $this->stmt->iterateNumeric();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function iterateAssociative(): Traversable
    {
        try {
            return $this->stmt->iterateAssociative();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function iterateKeyValue(): Traversable
    {
        try {
            return $this->stmt->iterateKeyValue();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function iterateAssociativeIndexed(): Traversable
    {
        try {
            return $this->stmt->iterateAssociativeIndexed();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     */
    public function iterateColumn(): Traversable
    {
        try {
            return $this->stmt->iterateColumn();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     * @throws MySQLException
     * @return int
     */
    public function rowCount()
    {
        try {
            return $this->stmt->rowCount();
        } catch (Throwable $e) {
            throw new MySQLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * wrapper method
     */
    public function free(): void
    {
        $this->stmt->free();
    }
}
