<?php


namespace Framework;


use ZM\Annotation\Swoole\OnSave;

class DataProvider
{
    public static $buffer_list = [];

    public static function getResourceFolder() {
        return self::getWorkingDir() . '/resources/';
    }

    public static function getWorkingDir() {
        if(LOAD_MODE == 0) return WORKING_DIR;
        elseif (LOAD_MODE == 1) return LOAD_MODE_COMPOSER_PATH;
        elseif (LOAD_MODE == 2) return realpath('.');
        return null;
    }

    public static function getDataConfig() {
        return CONFIG_DIR;
    }

    public static function addSaveBuffer($buf_name, $sub_folder = null) {
        $name = ($sub_folder ?? "") . "/" . $buf_name . ".json";
        self::$buffer_list[$buf_name] = $name;
        Console::debug("Added " . $buf_name . " at $sub_folder");
        ZMBuf::set($buf_name, self::getJsonData($name));
    }

    public static function saveBuffer() {
        $head = Console::setColor(date("[H:i:s] ") . "[V] Saving buffer......", "blue");
        if (ZMBuf::$atomics["info_level"]->get() >= 3)
            echo $head;
        foreach (self::$buffer_list as $k => $v) {
            Console::debug("Saving " . $k . " to " . $v);
            self::setJsonData($v, ZMBuf::get($k));
        }
        foreach (ZMBuf::$events[OnSave::class] ?? [] as $v) {
            $c = $v->class;
            $method = $v->method;
            $class = new $c();
            Console::debug("Calling @OnSave: $c -> $method");
            $class->$method();
        }
        if (ZMBuf::$atomics["info_level"]->get() >= 3)
            echo Console::setColor("saved", "blue") . PHP_EOL;
    }

    public static function getFrameworkLink() {
        return ZMBuf::globals("http_reverse_link");
    }

    public static function getJsonData(string $string) {
        if (!file_exists(self::getDataConfig() . $string)) return [];
        return json_decode(file_get_contents(self::getDataConfig() . $string), true);
    }

    public static function setJsonData($filename, array $args) {
        $pathinfo = pathinfo($filename);
        if (!is_dir(self::getDataConfig() . $pathinfo["dirname"])) {
            Console::debug("Making Directory: " . self::getDataConfig() . $pathinfo["dirname"]);
            mkdir(self::getDataConfig() . $pathinfo["dirname"]);
        }
        $r = file_put_contents(self::getDataConfig() . $filename, json_encode($args, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
        if ($r === false) {
            Console::warning("无法保存文件: " . $filename);
        }
    }

    public static function getDataFolder() {
        return ZM_DATA;
    }
}
