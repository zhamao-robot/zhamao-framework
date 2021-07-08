<?php


namespace ZM\MySQL;


class MySQLWrapper
{
    public $connection;

    public function __construct() {
        $this->connection = MySQLManager::getConnection();
    }

    public function __destruct() {
        $this->connection->close();
    }
}