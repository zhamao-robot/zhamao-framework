<?php

declare(strict_types=1);

namespace ZM\Store\MySQL;

use ZM\MySQL\MySQLPool;

class SqlPoolStorage
{
    /** @var null|MySQLPool */
    public static $sql_pool;
}
