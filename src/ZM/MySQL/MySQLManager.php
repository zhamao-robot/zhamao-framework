<?php


namespace ZM\MySQL;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

class MySQLManager
{
    /**
     * @return Connection
     * @throws Exception
     */
    public static function getConnection() {
        return DriverManager::getConnection(["driverClass" => MySQLDriver::class]);
    }
}