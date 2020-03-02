<?php


namespace ZM\DBCache;


class DHUerCache implements DBCache
{
    /**
     * @var array
     */
    private static $data;

    public static function reset() {
        self::$data = [];
    }
}