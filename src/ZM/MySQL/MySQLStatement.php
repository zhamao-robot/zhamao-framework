<?php

/**
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace ZM\MySQL;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Driver\StatementIterator;
use Doctrine\DBAL\ParameterType;
use IteratorAggregate;
use PDO;
use PDOStatement;

class MySQLStatement implements IteratorAggregate, Statement
{
    /** @var PDOStatement */
    private $statement;

    public function __construct($obj)
    {
        $this->statement = $obj;
    }

    public function closeCursor()
    {
        return $this->statement->closeCursor();
    }

    public function columnCount()
    {
        return $this->statement->columnCount();
    }

    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = [])
    {
        if ($arg2 !== null && $arg3 !== []) {
            return $this->statement->setFetchMode($fetchMode, $arg2, $arg3);
        }
        if ($arg2 !== null && $arg3 === []) {
            return $this->statement->setFetchMode($fetchMode, $arg2);
        }
        if ($arg2 === null && $arg3 !== []) {
            return $this->statement->setFetchMode($fetchMode, $arg2, $arg3);
        }

        return $this->statement->setFetchMode($fetchMode);
    }

    public function fetch($fetchMode = PDO::FETCH_ASSOC, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        return $this->statement->fetch($fetchMode, $cursorOrientation, $cursorOffset);
    }

    public function fetchAll($fetchMode = PDO::FETCH_ASSOC, $fetchArgument = null, $ctorArgs = null)
    {
        if ($fetchArgument === null && $ctorArgs === null) {
            return $this->statement->fetchAll($fetchMode);
        }
        if ($fetchArgument !== null && $ctorArgs === null) {
            return $this->statement->fetchAll($fetchMode, $fetchArgument);
        }

        return $this->statement->fetchAll($fetchMode, $fetchArgument, $ctorArgs);
    }

    public function fetchColumn($columnIndex = 0)
    {
        return $this->statement->fetchColumn($columnIndex);
    }

    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        return $this->statement->bindValue($param, $value, $type);
    }

    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        return $this->statement->bindParam($param, $variable, $type, $length);
    }

    public function errorCode()
    {
        return $this->statement->errorCode();
    }

    public function errorInfo()
    {
        return $this->statement->errorInfo();
    }

    public function execute($params = null)
    {
        return $this->statement->execute($params);
    }

    public function rowCount()
    {
        return $this->statement->rowCount();
    }

    public function getIterator(): StatementIterator
    {
        return new StatementIterator($this);
    }

    /**
     * @deprecated 最好不使用此方法，此方法可能存在 Bug
     * @return mixed
     */
    public function current()
    {
        if (method_exists($this->statement, 'current')) {
            return $this->statement->current();
        }
        return null;
    }
}
