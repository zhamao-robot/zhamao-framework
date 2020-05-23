<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/2/10
 * Time: 下午6:13
 */

namespace Framework;

use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Connection\WSConnection;
use ZM\Utils\ZMUtil;
use Exception;

class Console
{
    /**
     * @var false|resource
     */
    public static $console_proc = null;
    public static $pipes = [];

    static function setColor($string, $color = "") {
        switch ($color) {
            case "red":
                return "\x1b[38;5;203m" . $string . "\x1b[m";
            case "green":
                return "\x1b[38;5;83m" . $string . "\x1b[m";
            case "yellow":
                return "\x1b[38;5;227m" . $string . "\x1b[m";
            case "blue":
                return "\033[34m" . $string . "\033[0m";
            case "pink": // I really don't know what stupid color it is.
            case "lightpurple":
                return "\x1b[38;5;207m" . $string . "\x1b[m";
            case "lightblue":
                return "\x1b[38;5;87m" . $string . "\x1b[m";
            case "gold":
                return "\x1b[38;5;214m" . $string . "\x1b[m";
            case "gray":
                return "\x1b[38;5;59m" . $string . "\x1b[m";
            case "lightlightblue":
                return "\x1b[38;5;63m" . $string . "\x1b[m";
            default:
                return $string;
        }
    }

    static function error($obj, $head = null) {
        if ($head === null) $head = date("[H:i:s] ") . "[E] ";
        if (ZMBuf::$info_level !== null && in_array(ZMBuf::$info_level->get(), [1, 2])) {
            $trace = debug_backtrace()[1] ?? ['file' => '', 'function' => ''];
            $trace = "[" . basename($trace["file"], ".php") . ":" . $trace["function"] . "] ";
        }
        if (!is_string($obj)) {
            if (isset($trace)) {
                var_dump($obj);
                return;
            } else $obj = "{Object}";
        }
        echo(self::setColor($head . ($trace ?? "") . $obj, "red") . "\n");
    }

    static function warning($obj, $head = null) {
        if ($head === null) $head = date("[H:i:s]") . " [W] ";
        if (ZMBuf::$info_level !== null && in_array(ZMBuf::$info_level->get(), [1, 2])) {
            $trace = debug_backtrace()[1] ?? ['file' => '', 'function' => ''];
            $trace = "[" . basename($trace["file"], ".php") . ":" . $trace["function"] . "] ";
        }
        if (ZMBuf::$atomics["info_level"]->get() >= 1) {
            if (!is_string($obj)) {
                if (isset($trace)) {
                    var_dump($obj);
                    return;
                } else $obj = "{Object}";
            }
            echo(self::setColor($head . ($trace ?? "") . $obj, in_array("--white-term", FrameworkLoader::$argv) ? "blue" : "yellow") . "\n");
        }
    }

    static function info($obj, $head = null) {
        if ($head === null) $head = date("[H:i:s] ") . "[I] ";
        if (ZMBuf::$info_level !== null && in_array(ZMBuf::$info_level->get(), [1, 2])) {
            $trace = debug_backtrace()[1] ?? ['file' => '', 'function' => ''];
            $trace = "[" . basename($trace["file"], ".php") . ":" . $trace["function"] . "] ";
        }
        if (ZMBuf::$atomics["info_level"]->get() >= 2) {
            if (!is_string($obj)) {
                if (isset($trace)) {
                    var_dump($obj);
                    return;
                } else $obj = "{Object}";
            }
            echo(self::setColor($head . ($trace ?? "") . $obj, in_array("--white-term", FrameworkLoader::$argv) ? "black" : "lightblue") . "\n");
        }
    }

    static function success($obj, $head = null) {
        if ($head === null) $head = date("[H:i:s] ") . "[S] ";
        if (ZMBuf::$info_level !== null && in_array(ZMBuf::$info_level->get(), [1, 2])) {
            $trace = debug_backtrace()[1] ?? ['file' => '', 'function' => ''];
            $trace = "[" . basename($trace["file"], ".php") . ":" . $trace["function"] . "] ";
        }
        if (ZMBuf::$atomics["info_level"]->get() >= 2) {
            if (!is_string($obj)) {
                if (isset($trace)) {
                    var_dump($obj);
                    return;
                } else $obj = "{Object}";
            }
            echo(self::setColor($head . ($trace ?? "") . $obj, "green") . "\n");
        }
    }

    static function verbose($obj, $head = null) {
        if ($head === null) $head = date("[H:i:s] ") . "[V] ";
        if (ZMBuf::$atomics["info_level"]->get() >= 3) {
            if (!is_string($obj)) {
                if (isset($trace)) {
                    var_dump($obj);
                    return;
                } else $obj = "{Object}";
            }
            echo(self::setColor($head . ($trace ?? "") . $obj, "blue") . "\n");
        }
    }

    static function debug($msg) {
        if (ZMBuf::$atomics["info_level"]->get() >= 4) Console::log(date("[H:i:s] ") . "[D] " . $msg, 'gray');
    }

    static function log($obj, $color = "") {
        if (!is_string($obj)) var_dump($obj);
        else echo(self::setColor($obj, $color) . "\n");
    }

    static function stackTrace() {
        $log = "Stack trace:\n";
        $trace = debug_backtrace();
        //array_shift($trace);
        foreach ($trace as $i => $t) {
            if (!isset($t['file'])) {
                $t['file'] = 'unknown';
            }
            if (!isset($t['line'])) {
                $t['line'] = 0;
            }
            if (!isset($t['function'])) {
                $t['function'] = 'unknown';
            }
            $log .= "#$i {$t['file']}({$t['line']}): ";
            if (isset($t['object']) and is_object($t['object'])) {
                $log .= get_class($t['object']) . '->';
            }
            $log .= "{$t['function']}()\n";
        }
        $log = Console::setColor($log, "gray");
        echo $log;
    }

    static function listenConsole() {
        if (in_array('--disable-console-input', FrameworkLoader::$argv)) {
            self::info("ConsoleCommand disabled.");
            return;
        }
        global $terminal_id;
        global $port;
        $port = ZMBuf::globals("port");
        $vss = new SwooleEventAt();
        $vss->type = "open";
        $vss->level = 256;
        $vss->rule = "connectType:terminal";
        $terminal_id = call_user_func(function () {
            try {
                $data = random_bytes(16);
            } catch (Exception $e) {
                return "";
            }
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            return strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
        });
        $vss->callback = function(?WSConnection $conn) use ($terminal_id){
            $req = ctx()->getRequest();
            if($conn->getType() != "terminal") return false;
            if(($req->header["x-terminal-id"] ?? "") != $terminal_id || ($req->header["x-pid"] ?? "") != posix_getpid()) {
                $conn->close();
                return false;
            }
            return false;
        };
        ZMBuf::$events[SwooleEventAt::class][] = $vss;
        $vss2 = new SwooleEventAt();
        $vss2->type = "message";
        $vss2->rule = "connectType:terminal";
        $vss2->callback = function(?WSConnection $conn){
            if($conn->getType() != "terminal") return false;
            $cmd = ctx()->getFrame()->data;
            self::executeCommand($cmd);
            return false;
        };
        ZMBuf::$events[SwooleEventAt::class][] = $vss2;
        go(function () {
            global $terminal_id, $port;
            $descriptorspec = array(
                0 => STDIN,
                1 => STDOUT,
                2 => STDERR
            );
            self::$console_proc = proc_open('php -r \'$terminal_id = "'.$terminal_id.'";$port = '.$port.';require "'.__DIR__.'/terminal_listener.php";\'', $descriptorspec, $pipes);
        });
    }

    /**
     * @param string $cmd
     * @return bool
     */
    private static function executeCommand(string $cmd) {
        $it = explodeMsg($cmd);
        switch ($it[0] ?? '') {
            case 'logtest':
                Console::log(date("[H:i:s]") . " [L] This is normal msg. (0)");
                Console::error("This is error msg. (0)");
                Console::warning("This is warning msg. (1)");
                Console::info("This is info msg. (2)");
                Console::success("This is success msg. (2)");
                Console::verbose("This is verbose msg. (3)");
                Console::debug("This is debug msg. (4)");
                return true;
            case 'call':
                $class_name = $it[1];
                $function_name = $it[2];
                $class = new $class_name([]);
                call_user_func_array([$class, $function_name], []);
                return true;
            case 'bc':
                $code = base64_decode($it[1] ?? '', true);
                try {
                    eval($code);
                } catch (Exception $e) {
                }
                return true;
            case 'echo':
                Console::info($it[1]);
                return true;
            case 'color':
                Console::log($it[2], $it[1]);
                return true;
            case 'stop':
                ZMUtil::stop();
                return false;
            case 'reload':
            case 'r':
                ZMUtil::reload();
                return false;
            case 'save':
                $origin = ZMBuf::$atomics["info_level"]->get();
                //ZMBuf::$atomics["info_level"]->set(3);
                DataProvider::saveBuffer();
                //ZMBuf::$atomics["info_level"]->set($origin);
                return true;
            case '':
                return true;
            default:
                Console::info("Command not found: " . $cmd);
                return true;
        }
    }

    public static function withSleep(string $string, int $int) {
        self::info($string);
        sleep($int);
    }
}
