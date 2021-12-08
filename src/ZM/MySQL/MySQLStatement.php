<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\MySQL;


use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Driver\StatementIterator;
use Doctrine\DBAL\ParameterType;
use IteratorAggregate;
use PDO;
use PDOStatement;
use Swoole\Database\PDOStatementProxy;

class MySQLStatement implements IteratorAggregate, Statement
{
    /** @var PDOStatement|PDOStatementProxy */
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
        if ($arg2 !== null && $arg3 !== [])
            return $this->statement->setFetchMode($fetchMode, $arg2, $arg3);
        elseif ($arg2 !== null && $arg3 === [])
            return $this->statement->setFetchMode($fetchMode, $arg2);
        elseif ($arg2 === null && $arg3 !== [])
            return $this->statement->setFetchMode($fetchMode, $arg2, $arg3);
        else
            return $this->statement->setFetchMode($fetchMode);
    }

    public function fetch($fetchMode = PDO::FETCH_ASSOC, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        return $this->statement->fetch($fetchMode, $cursorOrientation, $cursorOffset);
    }

    public function fetchAll($fetchMode = PDO::FETCH_ASSOC, $fetchArgument = null, $ctorArgs = null)
    {
        if ($fetchArgument === null && $ctorArgs === null)
            return $this->statement->fetchAll($fetchMode);
        elseif ($fetchArgument !== null && $ctorArgs === null)
            return $this->statement->fetchAll($fetchMode, $fetchArgument);
        else
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

    public function getIterator()
    {
        return new StatementIterator($this);
    }

    public function current()
    {
        return $this->statement->current();
    }
}