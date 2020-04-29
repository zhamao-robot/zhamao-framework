<?php


namespace ZM\Utils;


use Co;
use Framework\Console;
use Framework\ZMBuf;

class DataProvider
{
    public static $buffer_list = [];

    public static function getResourceFolder()
    {
        return WORKING_DIR . '/resources/';
    }

    public static function getDataConfig()
    {
        return CONFIG_DIR;
    }

    public static function addSaveBuffer($buf_name, $sub_folder = null)
    {
        $name = ($sub_folder ?? "") . "/" . $buf_name . ".json";
        self::$buffer_list[$buf_name] = $name;
        ZMBuf::set($buf_name, self::getJsonData($name));
    }

    public static function saveBuffer()
    {
        $head = Console::setColor(date("[H:i:s] ") . "[V] Saving buffer......", "blue");
        if (ZMBuf::$atomics["info_level"]->get() >= 3)
            echo $head;
        foreach (self::$buffer_list as $k => $v) {
            self::setJsonData($v, ZMBuf::get($k));
        }
        if (ZMBuf::$atomics["info_level"]->get() >= 3)
            echo Console::setColor("saved", "blue") . PHP_EOL;
    }

    public static function getFrameworkLink()
    {
        return ZMBuf::globals("http_reverse_link");
    }

    public static function getJsonData(string $string)
    {
        if (!file_exists(self::getDataConfig() . $string)) return [];
        return json_decode(Co::readFile(self::getDataConfig() . $string), true);
    }

    private static function setJsonData($filename, array $args)
    {
        Co::writeFile(self::getDataConfig() . $filename, json_encode($args, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
    }

    public static function getDataFolder()
    {
        return ZM_DATA;
    }
}
