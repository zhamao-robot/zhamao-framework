<?php


namespace ZM\MySQL;


use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use ZM\Config\ZMConfig;
use ZM\Console\Console;

class MySQLDriver implements Driver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = []) {
        Console::info("Requiring new connection");
        return new MySQLConnection();
    }

    public function getDatabasePlatform(): MySqlPlatform {
        return new MySqlPlatform();
    }

    public function getSchemaManager($conn) {
        return new MySqlSchemaManager($conn);
    }

    public function getName() {
        return 'pdo_mysql_pool';
    }

    public function getDatabase($conn) {
        $params = ZMConfig::get("global", "mysql_config");

        if (isset($params['dbname'])) {
            return $params['dbname'];
        }
        return "";
    }
}