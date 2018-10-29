<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/6/14
 * Time: 11:04 AM
 */

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