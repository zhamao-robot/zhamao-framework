<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/14
 * Time: 12:54
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
        if (!file_exists(self::getDataFolder() . $filename)) return [];
        return json_decode(file_get_contents(self::getDataFolder() . $filename), true);
    }

    /**
     * 储存PHP数组为json文件，文件不存在则会创建文件
     * @param $filename
     * @param array $args
     */
    static function setJsonData($filename, array $args){
        file_put_contents(self::getDataFolder() . $filename, json_encode($args, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
    }
}