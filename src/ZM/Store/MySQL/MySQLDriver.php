<?php

declare(strict_types=1);

namespace ZM\Store\MySQL;

use Doctrine\DBAL\Driver as DoctrineDriver;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\MySqlSchemaManager;

class MySQLDriver implements DoctrineDriver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        logger()->debug('Requiring new connection');
        return new MySQLConnection($params);
    }

    public function getDatabasePlatform(): MySqlPlatform
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
        $conf = config('global.mysql');

        if ($conn instanceof MySQLConnection) {
            foreach ($conf as $v) {
                if (($v['name'] ?? $v['dbname']) === $conn->getPoolName()) {
                    return $v['dbname'];
                }
            }
        }
        return '';
    }
}
