<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/2/10
 * Time: 下午6:13
 */

namespace Framework;

use co;
use ZM\Utils\ZMUtil;
use Exception;

class Console
{
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
            case "lightpurple":
                return "\x1b[38;5;207m" . $string . "\x1b[m";
            case "lightblue":
                return "\x1b[38;5;87m" . $string . "\x1b[m";
            case "gold":
                return "\x1b[38;5;214m" . $string . "\x1b[m";
            case "gray":
                return "\x1b[38;5;59m" . $string . "\x1b[m";
            case "pink":
                return "\x1b[38;5;207m" . $string . "\x1b[m";
            case "lightlightblue":
                return "\x1b[38;5;63m" . $string . "\x1b[m";
            default:
                return $string;
        }
    }

    static function error($obj, $head = null) {
        if ($head === null) $head = date("[H:i:s ") . "ERROR] ";
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
        if ($head === null) $head = date("[H:i:s") . " WARN] ";
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
        echo(self::setColor($head . ($trace ?? "") . $obj, "yellow") . "\n");
    }

    static function info($obj, $head = null) {
        if ($head === null) $head = date("[H:i:s ") . "INFO] ";
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
        echo(self::setColor($head . ($trace ?? "") . $obj, "lightblue") . "\n");
    }

    static function log($obj, $color = "") {
        if (!is_string($obj)) var_dump($obj);
        else echo(self::setColor($obj, $color) . "\n");
    }

    static function msg($obj, $self_id = "") {
        if (ZMBuf::$info_level !== null && ZMBuf::$info_level->get() == 3) {
            if (!isset($obj["post_type"])) {
                switch ($obj["action"]) {
                    case "send_private_msg":
                        $msg = Console::setColor(date("H:i:s ") . "[" . (ZMBuf::globals("robot_alias")[$self_id] ?? "Null") . "] ", "lightlightblue");
                        $msg .= Console::setColor("私聊↑(" . $obj["params"]["user_id"] . ")", "lightlightblue");
                        $msg .= Console::setColor(" > ", "gray");
                        $msg .= $obj["params"]["message"];
                        Console::log($msg);
                        break;
                    case "send_group_msg":
                        //TODO: 写新的控制台消息（API消息处理）
                        Console::log(Console::setColor("[" . date("H:i:s") . " GROUP:" . $obj["params"]["group_id"] . "] ", "blue") . Console::setColor($obj["params"]["user_id"] ?? "", "yellow") . Console::setColor(" > ", "gray") . ($obj["params"]["message"] ?? ""));
                        break;
                    case "send_discuss_msg":
                        Console::log(Console::setColor("[" . date("H:i:s") . " DISCUSS:" . $obj["params"]["discuss_id"] . "] ", "blue") . Console::setColor($obj["params"]["user_id"] ?? "", "yellow") . Console::setColor(" > ", "gray") . ($obj["params"]["message"] ?? ""));
                        break;
                    case "send_msg":
                        $obj["action"] = "send_" . $obj["message_type"] . "_msg";
                        self::msg($obj);
                        break;
                    case "send_wechat_msg":
                        Console::log(Console::setColor("[" . date("H:i:s") . " WECHAT] ", "blue") . Console::setColor($obj["params"]["user_id"] ?? "", "yellow") . Console::setColor(" > ", "gray") . ($obj["params"]["message"] ?? ""));
                        break;
                    default:
                        break;
                }
            } else {
                if ($obj["post_type"] == "message") {
                    switch ($obj["message_type"]) {
                        case "group":
                            //
                            //TODO: 写新的控制台消息（event处理）
                        case "private":
                        case "discuss":
                        case "wechat":
                    }
                }
            }
        }
    }

    static function stackTrace(){
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

    static function listenConsole(){
        go(function () {
            while (true) {
                $cmd = trim(co::fread(STDIN));
                if (self::executeCommand($cmd) === false) break;
            }
        });
    }

    /**
     * @param string $cmd
     * @return bool
     */
    private static function executeCommand(string $cmd) {
        $it = explodeMsg($cmd);
        switch ($it[0] ?? '') {
            case 'call':
                $class_name = $it[1];
                $function_name = $it[2];
                $class = new $class_name([]);
                call_user_func_array([$class, $function_name],[]);
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
            case 'stop':
                ZMUtil::stop();
                return false;
            case 'reload':
            case 'r':
                ZMUtil::reload();
                return false;
            case '':
                return true;
            default:
                Console::info("Command not found: " . $it[0]);
                return true;
        }
    }

    public static function withSleep(string $string, int $int) {
        self::info($string);
        sleep($int);
    }
}