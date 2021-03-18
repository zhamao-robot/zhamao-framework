<?php /** @noinspection PhpUnused */


namespace ZM\Utils;


use ZM\Config\ZMConfig;
use ZM\Console\Console;

class DataProvider
{
    public static $buffer_list = [];

    public static function getResourceFolder(): string {
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

    public static function saveToJson($filename, $file_array) {
        $path = ZMConfig::get("global", "config_dir");
        $r = explode("/", $filename);
        if(count($r) == 2) {
            $path = $path . $r[0]."/";
            if(!is_dir($path)) mkdir($path);
            $name = $r[1];
        } elseif (count($r) != 1) {
            Console::warning("存储失败，文件名只能有一级目录");
            return false;
        } else {
            $name = $r[0];
        }
        return file_put_contents($path.$name.".json", json_encode($file_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public static function loadFromJson($filename) {
        $path = ZMConfig::get("global", "config_dir");
        if(file_exists($path.$filename.".json")) {
            return json_decode(file_get_contents($path.$filename.".json"), true);
        } else {
            return null;
        }
    }
}
