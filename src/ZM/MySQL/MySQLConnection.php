<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\MySQL;


use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use PDOStatement;
use Swoole\Database\PDOProxy;
use Swoole\Database\PDOStatementProxy;
use ZM\Console\Console;
use ZM\Exception\DbException;
use ZM\Store\MySQL\SqlPoolStorage;

class MySQLConnection implements Connection
{
    /** @var PDO|PDOProxy */
    private $conn;

    public function __construct() {
        Console::debug("Constructing...");
        $this->conn = SqlPoolStorage::$sql_pool->getConnection();
    }

    public function prepare($sql, $options = []) {
        try {
            Console::debug("Running SQL prepare: ".$sql);
            $statement = $this->conn->prepare($sql, $options);
            assert(($statement instanceof PDOStatementProxy) || ($statement instanceof PDOStatement));
        } catch (PDOException $exception) {
            throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return new MySQLStatement($statement);
    }

    public function query(...$args) {
        try {
            $statement = $this->conn->query(...$args);
            assert(($statement instanceof PDOStatementProxy) || ($statement instanceof PDOStatement));
        } catch (PDOException $exception) {
            throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return new MySQLStatement($statement);
    }

    public function quote($value, $type = ParameterType::STRING) {
        return $this->conn->quote($value, $type);
    }

    public function exec($sql) {
        try {
            Console::debug("Running SQL exec: ".$sql);
            $statement = $this->conn->exec($sql);
            assert($statement !== false);
            return $statement;
        } catch (PDOException $exception) {
            throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function lastInsertId($name = null) {
        try {
            return $name === null ? $this->conn->lastInsertId() : $this->conn->lastInsertId($name);
        } catch (PDOException $exception) {
            throw new DbException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollBack() {
        return $this->conn->rollBack();
    }

    public function errorCode() {
        return $this->conn->errorCode();
    }

    public function errorInfo() {
        return $this->conn->errorInfo();
    }

    public function __destruct() {
        Console::debug("Destructing！！！");
        SqlPoolStorage::$sql_pool->putConnection($this->conn);
    }
}