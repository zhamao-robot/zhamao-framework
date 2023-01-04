<?php

declare(strict_types=1);

namespace ZM\Store\Database;

use Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Doctrine\DBAL\Driver\Connection;

class PgSQLDriver extends AbstractPostgreSQLDriver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        logger()->debug('Requiring new connection');
        return new DBConnection($params);
    }

    public function getName()
    {
        return 'pdo_pgsql_pool';
    }

    public function getDatabase(Connection $conn)
    {
        if ($conn instanceof DBConnection) {
            return config('database')[$conn->getPoolName()]['dbname'] ?? '';
        }
        return '';
    }
}
