<?php

declare(strict_types=1);

namespace ZM\Store\Database;

use Doctrine\DBAL\Driver as DoctrineDriver;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\MySqlSchemaManager;

class MySQLDriver implements DoctrineDriver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        logger()->debug('Requiring new connection');
        return new DBConnection($params);
    }

    public function getDatabasePlatform()
    {
        return new MySqlPlatform();
    }

    public function getSchemaManager($conn)
    {
        return new MySqlSchemaManager($conn);
    }

    public function getName()
    {
        return 'pdo_mysql_pool';
    }

    public function getDatabase($conn)
    {
        if ($conn instanceof DBConnection) {
            return config('database')[$conn->getPoolName()]['dbname'] ?? '';
        }
        return '';
    }
}
