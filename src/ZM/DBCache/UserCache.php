<?php


namespace ZM\DBCache;


class UserCache implements DBCache
{

    /**
     * @var array
     */
    private static $data;

    public static function reset() {
        self::$data = [];
    }
}