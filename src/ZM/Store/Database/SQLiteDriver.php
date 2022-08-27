<?php

declare(strict_types=1);

namespace ZM\Store\Database;

use Doctrine\DBAL\Driver as DoctrineDriver;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\SqliteSchemaManager;

class SQLiteDriver implements DoctrineDriver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        logger()->debug('Requiring new connection');
        return new DBConnection($params);
    }

    public function getDatabasePlatform()
    {
        return new SqlitePlatform();
    }

    public function getSchemaManager($conn)
    {
        return new SqliteSchemaManager($conn);
    }

    public function getName()
    {
        return 'pdo_sqlite_pool';
    }

    public function getDatabase($conn)
    {
        if ($conn instanceof DBConnection) {
            return config('database')[$conn->getPoolName()]['dbname'] ?? '';
        }
        return '';
    }
}
