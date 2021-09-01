<?php


namespace ZM\MySQL;


class MySQLManager
{
    /**
     * @return MySQLWrapper
     */
    public static function getWrapper(): MySQLWrapper {
        return new MySQLWrapper();
    }
}