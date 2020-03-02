<?php


namespace Framework;


use Co;
use Exception;
use Swoole\Coroutine;
use swoole\server;

class RemoteShell
{
    const STX = "DEBUG";
    private static $contexts = array();
    static $oriPipeMessageCallback = null;
    /**
     * @var server
     */
    static $serv;
    static $menu = array(
        "p|print [variant]\t打印一个PHP变量的值",
        "e|exec [code]\t执行一段PHP代码",
        "w|worker [id]\t切换Worker进程",
        "l|list\t打印服务器所有连接的fd",
        "s|stats\t打印服务器状态",
        "c|coros\t打印协程列表",
        "b|bt\t打印协程调用栈",
        "i|info [fd]\t显示某个连接的信息",
        "h|help\t显示帮助界面",
        "q|quit\t退出终端",
    );
    const PAGESIZE = 20;

    /**
     * @param $serv server
     * @param string $host
     * @param int $port
     * @throws Exception
     * @throws Exception
     */
    static function listen($serv, $host = "127.0.0.1", $port = 9599) {
        Console::warning("正在监听".$host.":".strval($port)."的调试接口，请注意安全");
        $port = $serv->listen($host, $port, SWOOLE_SOCK_TCP);
        if (!$port) {
            throw new Exception("listen fail.");
        }
        $port->set(array(
            "open_eof_split" => true,
            'package_eof' => "\r\n",
        ));
        $port->on("Connect", array(__CLASS__, 'onConnect'));
        $port->on("Close", array(__CLASS__, 'onClose'));
        $port->on("Receive", array(__CLASS__, 'onReceive'));
        if (method_exists($serv, 'getCallback')) {
            self::$oriPipeMessageCallback = $serv->getCallback('PipeMessage');
        }
        $serv->on("PipeMessage", array(__CLASS__, 'onPipeMessage'));
        self::$serv = $serv;
    }

    static function onConnect($serv, $fd, $reactor_id) {
        self::$contexts[$fd]['worker_id'] = $serv->worker_id;
        self::output($fd, implode("\r\n", self::$menu));
    }

    static function output($fd, $msg) {
        if (!isset(self::$contexts[$fd]['worker_id'])) {
            $msg .= "\r\nworker#" . self::$serv->worker_id . "$ ";
        } else {
            $msg .= "\r\nworker#" . self::$contexts[$fd]['worker_id'] . "$ ";
        }
        self::$serv->send($fd, $msg);
    }

    static function onClose($serv, $fd, $reactor_id) {
        unset(self::$contexts[$fd]);
    }

    static function onPipeMessage($serv, $src_worker_id, $message) {
        //不是 debug 消息
        if (!is_string($message) or substr($message, 0, strlen(self::STX)) != self::STX) {
            if (self::$oriPipeMessageCallback == null) {
                trigger_error("require swoole-4.3.0 or later.", E_USER_WARNING);
                return true;
            }
            return call_user_func(self::$oriPipeMessageCallback, $serv, $src_worker_id, $message);
        } else {
            $request = unserialize(substr($message, strlen(self::STX)));
            self::call($request['fd'], $request['func'], $request['args']);
        }
        return true ;
    }

    static protected function call($fd, $func, $args) {
        ob_start();
        call_user_func_array($func, $args);
        self::output($fd, ob_get_clean());
    }

    static protected function exec($fd, $func, $args) {
        //不在当前Worker进程
        if (self::$contexts[$fd]['worker_id'] != self::$serv->worker_id) {
            self::$serv->sendMessage(self::STX . serialize(['fd' => $fd, 'func' => $func, 'args' => $args]), self::$contexts[$fd]['worker_id']);
        } else {
            self::call($fd, $func, $args);
        }
    }

    static function getCoros() {
        var_export(iterator_to_array(Coroutine::listCoroutines()));
    }

    static function getBackTrace($_cid) {
        $info = Co::getBackTrace($_cid);
        if (!$info) {
            echo "coroutine $_cid not found.";
        } else {
            echo get_debug_print_backtrace($info);
        }
    }

    static function printVariant($var) {
        $var = ltrim($var, '$ ');
        var_dump($var);
        var_dump($$var);
    }

    static function evalCode($code) {
        eval($code . ';');
    }

    /**
     * @param $serv server
     * @param $fd
     * @param $reactor_id
     * @param $data
     */
    static function onReceive($serv, $fd, $reactor_id, $data) {
        $args = explode(" ", $data, 2);
        $cmd = trim($args[0]);
        unset($args[0]);
        switch ($cmd) {
            case 'w':
            case 'worker':
                if (!isset($args[1])) {
                    self::output($fd, "invalid command.");
                    break;
                }
                $dstWorkerId = intval($args[1]);
                self::$contexts[$fd]['worker_id'] = $dstWorkerId;
                self::output($fd, "[switching to worker " . self::$contexts[$fd]['worker_id'] . "]");
                break;
            case 'e':
            case 'exec':
                if (!isset($args[1])) {
                    self::output($fd, "invalid command.");
                    break;
                }
                $var = trim($args[1]);
                self::exec($fd, 'self::evalCode', [$var]);
                break;
            case 'p':
            case 'print':
                $var = trim($args[1]);
                self::exec($fd, 'self::printVariant', [$var]);
                break;
            case 'h':
            case 'help':
                self::output($fd, implode("\r\n", self::$menu));
                break;
            case 's':
            case 'stats':
                $stats = $serv->stats();
                self::output($fd, var_export($stats, true));
                break;
            case 'c':
            case 'coros':
                self::exec($fd, 'self::getCoros', []);
                break;
            /**
             * 查看协程堆栈
             */
            case 'bt':
            case 'b':
            case 'backtrace':
                if (empty($args[1])) {
                    self::output($fd, "invalid command [" . trim($args[1]) . "].");
                    break;
                }
                $_cid = intval($args[1]);
                self::exec($fd, 'self::getBackTrace', [$_cid]);
                break;
            case 'i':
            case 'info':
                if (empty($args[1])) {
                    self::output($fd, "invalid command [" . trim($args[1]) . "].");
                    break;
                }
                $_fd = intval($args[1]);
                $info = $serv->getClientInfo($_fd);
                if (!$info) {
                    self::output($fd, "connection $_fd not found.");
                } else {
                    self::output($fd, var_export($info, true));
                }
                break;
            case 'l':
            case 'list':
                $tmp = array();
                foreach ($serv->connections as $fd) {
                    $tmp[] = $fd;
                    if (count($tmp) > self::PAGESIZE) {
                        self::output($fd, json_encode($tmp));
                        $tmp = array();
                    }
                }
                if (count($tmp) > 0) {
                    self::output($fd, json_encode($tmp));
                }
                break;
            case 'q':
            case 'quit':
                $serv->close($fd);
                break;
            default:
                self::output($fd, "unknow command[$cmd]");
                break;
        }
    }
}

function get_debug_print_backtrace($traces) {
    $ret = array();
    foreach ($traces as $i => $call) {
        $object = '';
        if (isset($call['class'])) {
            $object = $call['class'] . $call['type'];
            if (is_array($call['args'])) {
                foreach ($call['args'] as &$arg) {
                    get_arg($arg);
                }
            }
        }
        $ret[] = '#' . str_pad($i, 3, ' ')
            . $object . $call['function'] . '(' . implode(', ', $call['args'])
            . ') called at [' . $call['file'] . ':' . $call['line'] . ']';
    }
    return implode("\n", $ret);
}

function get_arg(&$arg) {
    if (is_object($arg)) {
        $arr = (array)$arg;
        $args = array();
        foreach ($arr as $key => $value) {
            if (strpos($key, chr(0)) !== false) {
                $key = '';    // Private variable found
            }
            $args[] = '[' . $key . '] => ' . get_arg($value);
        }
        $arg = get_class($arg) . ' Object (' . implode(',', $args) . ')';
    }
}