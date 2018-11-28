<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/14
 * Time: 12:54
 */

/**
 * 此类中使用的读取和写入文件等IO有区分同步IO和异步IO，请（尽量）不要在事件循环中使用过多阻塞IO的方法。
 * 如使用同步逻辑，推荐将数据写入内存缓存类Cache中后进行读写，使用定时器或关闭服务时储存。
 */
class DataProvider
{
    /**
     * 获取config文件夹
     * @return string
     */
    public static function getDataFolder(){
        return CONFIG_DIR;
    }

    /**
     * 获取用户数据的文件夹
     * @return string
     */
    public static function getUserFolder(){
        return USER_DIR;
    }

    /**
     * 打开json文件并转换为PHP数组，文件不存在则返回空数组
     * @param $filename
     * @return array|mixed
     */
    static function getJsonData($filename){
        if (!file_exists(self::getDataFolder() . $filename)) return array();
        return json_decode(file_get_contents(self::getDataFolder() . $filename), true);
    }

    /**
     * 储存PHP数组为json文件，文件不存在则会创建文件。
     * 此方式为同步阻塞执行，可能会阻塞worker进程。
     * @param $filename
     * @param array $args
     */
    static function setJsonData($filename, array $args){
        file_put_contents(self::getDataFolder() . $filename, json_encode($args, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
    }

    /**
     * 储存PHP数组为json文件，文件不存在会创建文件
     * 此方式为异步非阻塞执行，不会对worker造成阻塞。
     * @param $filename
     * @param array $arg
     * @param callable|null $function
     */
    static function setJsonDataAsync($filename, array $arg, callable $function = null) {
        $data = json_encode($arg, 128 | 256);
        $filename = self::getDataFolder() . $filename;
        if ($function === null) swoole_async_writefile($filename, $data, function () { });
        else swoole_async_writefile($filename, $data, $function);
    }
}