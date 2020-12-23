<?php


namespace ZM\Store\MySQL;


use Swoole\Database\PDOPool;

class SqlPoolStorage
{
    /** @var PDOPool */
    public static $sql_pool = null;
}
