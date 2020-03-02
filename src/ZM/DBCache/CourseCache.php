<?php


namespace ZM\DBCache;


class CourseCache implements DBCache
{

    /**
     * @var array
     */
    private static $data;

    public static function reset() {
        self::$data = [];
    }
}