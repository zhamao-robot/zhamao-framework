<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/3/29
 * Time: 11:30
 */


class Cache
{
    /** @var int */
    static $worker_id = -1;
    /** @var array Cache data */
    static $data = [];
    /** @var \swoole_websocket_server $server */
    static $server;
    /** @var Scheduler $scheduler */
    static $scheduler;

    //共享内存的原子操作数据部分
    /** @var \swoole_atomic $in_count */
    static $in_count;//接收消息
    /** @var \swoole_atomic $out_count */
    static $out_count;//发送消息数量
    /** @var swoole_atomic $reload_time */
    static $reload_time;
    /** @var \swoole_atomic $api_id */
    static $api_id;

    /** @var WSConnection[] $connect */
    static $connect = [];

    static function get($name) { return self::$data[$name] ?? null; }

    static function set($name, $value) { self::$data[$name] = $value; }

    static function append($name, $value) { self::$data[$name][] = $value; }

    static function appendKey($name, $key, $value) { self::$data[$name][$key] = $value; }

    static function removeKey($name, $key) { unset(self::$data[$name][$key]); }

    static function removeValue($name, $vale) {
        $key = array_search($vale, self::$data[$name]);
        array_splice(self::$data[$name], $key, 1);
    }

    static function unset($name) { unset(self::$data[$name]); }

    static function isset($name) { return isset(self::$data[$name]); }

    static function array_key_exists($name, $key) { return isset(self::$data[$name][$key]); }

    static function in_array($name, $value) {
        if (!is_array((self::$data[$name] ?? 1))) return false;
        return in_array($value, self::$data[$name]);
    }
}