<?php

declare(strict_types=1);

namespace ZM\MySQL;

class MySQLManager
{
    public static function getWrapper(): MySQLWrapper
    {
        return new MySQLWrapper();
    }
}
