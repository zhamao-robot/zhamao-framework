<?php


namespace ZM\DBCache;


class GroupCache implements DBCache
{

    /**
     * @var array
     */
    private static $data;

    public static function reset() {
        self::$data = [];
    }
}