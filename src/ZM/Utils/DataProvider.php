<?php


namespace ZM\Utils;


use ZM\Config\ZMConfig;

class DataProvider
{
    public static $buffer_list = [];

    public static function getResourceFolder() {
        return self::getWorkingDir() . '/resources/';
    }

    public static function getWorkingDir() {
        if (LOAD_MODE == 0) return WORKING_DIR;
        elseif (LOAD_MODE == 1) return LOAD_MODE_COMPOSER_PATH;
        elseif (LOAD_MODE == 2) return realpath('.');
        return null;
    }

    public static function getFrameworkLink() {
        return ZMConfig::get("global", "http_reverse_link");
    }

    public static function getDataFolder() {
        return ZM_DATA;
    }
}
