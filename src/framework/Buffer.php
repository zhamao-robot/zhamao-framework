<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/3/29
 * Time: 11:30
 */


class Buffer
{
    static $data = [];
    static $api_session = [];
    /** @var \swoole_http_client $api */
    static $api;
    /** @var \swoole_websocket_server $event */
    static $event;
    /** @var string $log_file */
    static $log_file = "";
    /** @var \swoole_server $comm */
    static $comm = null;
    /** @var \swoole_atomic $in_count */
    static $in_count;//接收消息
    /** @var \swoole_atomic $out_count */
    static $out_count;//发送消息数量
    /** @var WSConnection[] */
    static $connect = [];
    /** @var swoole_atomic $reload_time */
    static $reload_time;

    static function get($name){ return self::$data[$name] ?? null; }

    static function set($name, $value){ self::$data[$name] = $value; }

    static function append($name, $value){ self::$data[$name][] = $value; }

    static function appendKey($name, $key, $value){ self::$data[$name][$key] = $value; }

    static function unset($name, $key){ unset(self::$data[$name][$key]); }

    static function unsetByValue($name, $vale){
        $key = array_search($vale, self::$data[$name]);
        array_splice(self::$data[$name], $key, 1);
    }

    static function isset($name){ return isset(self::$data[$name]); }

    static function array_key_exists($name, $key){ return isset(self::$data[$name][$key]); }

    static function in_array($name, $value){
        if(!is_array((self::$data[$name] ?? 1))) return false;
        return in_array($value, self::$data[$name]);
    }

    ////////////预留部分，为redis更新作准备/////////////

    /** @var string[] 为未来支持redis数据库做准备 */
    static $vars = [];
    static $ls = [];

    static function _get(string $name){

    }

    static function _setString(string $key, string $value){

    }

    static function _setList(string $key, array $value){

    }

    static function _appendList(string $key, string $value){

    }

    static function _ping(){

    }
}