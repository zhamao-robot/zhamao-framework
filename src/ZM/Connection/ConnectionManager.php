<?php


namespace ZM\Connection;


use Framework\ZMBuf;

class ConnectionManager
{
    /**
     * 通过server的fd获取WSConnection实例化对象
     * @param int $fd
     * @return WSConnection|CQConnection|ProxyConnection
     */
    public static function get(int $fd) {
        foreach (ZMBuf::$connect as $v) {
            if ($v->fd == $fd) return $v;
        }
        return null;
    }

    /**
     * @param string $type
     * @param array $option
     * @return WSConnection[]|CQConnection[]
     */
    public static function getByType(string $type, $option = []) {
        $conn = [];
        foreach (ZMBuf::$connect as $v) {
            foreach ($option as $ks => $vs) {
                if (($v->$ks ?? "") == $vs) continue;
                else continue 2;
            }
            if ($v->getType() == $type) $conn[] = $v;
        }
        return $conn;
    }

    public static function getTypeClassName(string $type) {
        switch (strtolower($type)) {
            case "qq":
            case "universal":
                return CQConnection::class;
            case "webconsole":
                return WCConnection::class;
            case "proxy":
                return ProxyConnection::class;
            default:
                foreach (ZMBuf::$custom_connection_class as $v) {
                    /** @var WSConnection $r */
                    $r = new $v(ZMBuf::$server, -1);
                    if ($r->getType() == strtolower($type)) return $v;
                }
                return UnknownConnection::class;
        }
    }

    public static function close($fd) {
        foreach (ZMBuf::$connect as $k => $v) {
            if ($v->fd == $fd) {
                ZMBuf::$server->close($fd);
                unset(ZMBuf::$connect[$k]);
                break;
            }
        }
    }

    public static function registerCustomClass() {
        $classes = getAllClasses(WORKING_DIR . "/src/Custom/Connection/", "Custom\\Connection");
        ZMBuf::$custom_connection_class = $classes;
    }
}
