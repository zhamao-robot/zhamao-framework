<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/11/26
 * Time: 10:20 AM
 */

class ConnectionManager
{
    static function getAll($type = null){
        if ($type === null) return Cache::$connect;
        $ls = [];
        foreach (Cache::$connect as $k => $v) {
            switch ($type) {
                case "robot":
                    /** @var RobotWSConnection[] $ls */
                    if ($v instanceof RobotWSConnection) $ls[] = $v;
                    break;
                default:
                    break;
            }
        }
        return $ls;
    }

    public static function get($fd) { return Cache::$connect[$fd] ?? null; }

    public static function set($fd, WSConnection $connection) { Cache::$connect[$fd] = $connection; }

    public static function remove($fd) { unset(Cache::$connect[$fd]); }

    public static function isConnectExists($fd) { return array_key_exists($fd, Cache::$connect); }

    /**
     * @param $qq
     * @return null|RobotWSConnection|NullConnection
     */
    public static function getRobotConnection($qq) {
        foreach (Cache::$connect as $v) {
            if ($v instanceof RobotWSConnection && $v->getQQ() == $qq && $v->getSubType() != "event") return $v;
        }
        return new NullConnection(Cache::$server, -1, "0.0.0.0", $qq);
    }
}