<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/11/22
 * Time: 2:42 PM
 */

/**
 * 获取全局配置
 * 此函数使用同步阻塞IO，请不要在功能逻辑代码中使用此函数。
 * @return mixed
 */
function settings() {
    return json_decode(file_get_contents(WORKING_DIR . "/cqbot.json"), true);
}

register_shutdown_function(function () {
    $error = error_get_last();
    if (isset($error['type'])) {
        switch ($error['type']) {
            case E_ERROR :
            case E_PARSE :
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
                $time = date('Y-m-d H:i:s', time());
                $message = $error['message'];
                $file = $error['file'];
                $line = $error['line'];
                $log = "[$time] $message ($file:$line)\nStack trace:\n";
                $trace = debug_backtrace();
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

                file_put_contents(CRASH_DIR . "last_error.log", $log);
                break;
            default:
                break;
        }
    }
});


function CQMsg($msg, $type, $id) {
    if ($type === "group") {
        $reply = ["action" => "send_group_msg", "params" => ["group_id" => $id, "message" => $msg]];
        $reply["echo"] = $reply;
        $reply["echo"]["time"] = time();
        $reply = json_encode($reply);
    } else if ($type === "private") {
        $reply = ["action" => "send_private_msg", "params" => ["user_id" => $id, "message" => $msg]];
        $reply["echo"] = $reply;
        $reply["echo"]["time"] = time();
        $reply = json_encode($reply);
    } else if ($type === "discuss") {
        $reply = ["action" => "send_discuss_msg", "params" => ["discuss_id" => $id, "message" => $msg]];
        $reply["echo"] = $reply;
        $reply["echo"]["time"] = time();
        $reply = json_encode($reply);
    } else {
        $reply = false;
    }
    return $reply;
}

function getClassPath($dir, $class_name) {
    $list = scandir($dir);
    unset($list[0], $list[1]);
    foreach ($list as $v) {
        $taskFileName = explode(".", $v);
        if (is_dir($dir . $v)) {
            if (($find = getClassPath($dir . $v . "/", $class_name)) !== null) return $find;
            else continue;
        } else {
            if (array_pop($taskFileName) == "php" && $taskFileName[0] == $class_name) return $dir . $v;
        }
    }
    return null;
}

function class_loader($p) {
    $dir = WORKING_DIR . "src/";
    $filepath = getClassPath($dir, $p);
    require_once $filepath;
}

function load_extensions() {
    $dir = WORKING_DIR . "src/extension/";
    $ls = scandir($dir);
    unset($ls[0], $ls[1]);
    foreach ($ls as $k => $v) {
        if (mb_substr($v, -4) == "phar") {
            require_once $dir . $v;
        }
    }
}

function color($str, $end = "\n") {
    $str = str_replace("{red}", "\e[38;5;203m", $str);
    $str = str_replace("{green}", "\e[38;5;83m", $str);
    $str = str_replace("{yellow}", "\e[38;5;227m", $str);
    $str = str_replace("{lightpurple}", "\e[38;5;207m", $str);
    $str = str_replace("{lightblue}", "\e[38;5;87m", $str);
    $str = str_replace("{gold}", "\e[38;5;214m", $str);
    $str = str_replace("{gray}", "\e[38;5;59m", $str);
    $str = str_replace("{pink}", "\e[38;5;207m", $str);
    $str = str_replace("{lightlightblue}", "\e[38;5;63m", $str);
    $str = str_replace("{r}", "\e[m", $str);
    $str .= "\e[m" . $end;
    return $str;
}

/**
 * 使用自己定义的万（san）能分割函数
 * @param $msg
 * @param bool $ban_comma
 * @return array
 */
function explodeMsg($msg, $ban_comma = false) {
    $msg = str_replace(" ", "\n", trim($msg));
    if (!$ban_comma) {
        $msg = str_replace("，", "\n", $msg);
        $msg = str_replace("\t", "\n", $msg);
    }
    $msgs = explode("\n", $msg);
    $ls = [];
    foreach ($msgs as $k => $v) {
        if (trim($v) == "") continue;
        $ls[] = trim($v);
    }
    return $ls;
}

/**
 * Unicode解析
 * @param $str
 * @return null|string|string[]
 */
function unicodeDecode($str) {
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function ($matches) {
        return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");
    }, $str);
}
