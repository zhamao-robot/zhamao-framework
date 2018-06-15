<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/6/14
 * Time: 11:04 AM
 */

date_default_timezone_set("Asia/Shanghai");

//启动时间
define("START_TIME", time());
@mkdir(CONFIG_DIR, 0777, true);
@mkdir(USER_DIR, 0777, true);
register_shutdown_function('handleFatal');

if (!file_exists(CONFIG_DIR . "config.json")) file_put_contents(CONFIG_DIR . "config.json", json_encode([]));

function handleFatal() {
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

                file_put_contents(CONFIG_DIR . "last_error.log", $log);
                break;
            default:
                break;
        }
    }
}

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

function printHelp() {
    echo color("{gold}=====CQBot-swoole=====");
    echo color("{gold}* 首次使用设置 *");
    echo color("[{green}?{r}] {lightlightblue}查看此列表");
    echo color("[{green}1{r}] {yellow}设置监听地址");
    echo color("[{green}2{r}] {yellow}设置监听端口");
    echo color("[{green}3{r}] {yellow}设置管理群");
    echo color("[{green}4{r}] {yellow}设置管理员");
    echo color("[{green}5{r}] {lightlightblue}开始运行");
}

function setupWizard(&$json, &$properties) {
    printHelp();
    while (true) {
        echo color("> ", "");
        $id = trim(fgets(STDIN));
        switch ($id) {
            case "1":
                echo color("请输入监听地址（默认0.0.0.0）：", "");
                $host = trim(fgets(STDIN));
                if ($host == "") {
                    $properties["host"] = "0.0.0.0";
                    echo color("{gray}已设置地址：0.0.0.0（默认）");
                } else {
                    $properties["host"] = $host;
                    echo color("{gray}已设置地址：" . $host);
                }
                break;
            case "2":
                echo color("请输入监听端口（默认20000）：", "");
                $host = trim(fgets(STDIN));
                if ($host == "") {
                    $properties["port"] = 20000;
                    echo color("{gray}已设置端口：20000（默认）");
                } else {
                    $properties["port"] = $host;
                    echo color("{gray}已设置端口：" . $host);
                }
                break;
            case "3":
                echo color("请输入机器人QQ号：", "");
                $self_id = trim(fgets(STDIN));
                if ($self_id == "") {
                    echo color("{red}请勿输入空数据！");
                    break;
                }
                echo color("请输入本机器人QQ的管理群（机器人必须已经在群内）：", "");
                $group = trim(fgets(STDIN));
                if ($group == "") {
                    echo color("{red}请勿输入空数据！");
                    break;
                }
                $properties["admin_group"][$self_id][] = $group;
                echo color("{gray}已设置机器人" . $self_id . "的管理群：" . $group);
                break;
            case "4":
                echo color("请输入机器人QQ号：", "");
                $self_id = trim(fgets(STDIN));
                if ($self_id == "") {
                    echo color("{red}请勿输入空数据！");
                    break;
                }
                echo color("请输入本机器人QQ的管理员QQ：", "");
                $group = trim(fgets(STDIN));
                if ($group == "") {
                    echo color("{red}请勿输入空数据！");
                    break;
                }
                $properties["super_user"][$self_id][] = $group;
                echo color("{gray}已设置机器人" . $self_id . "的管理员：" . $group);
                break;
            case "5":
                break 2;
            case "?":
            case "？":
                printHelp();
                break;
            default:
                echo color("{red}请输入正确的编号进行操作！\n在设置监听端口和监听地址后可开始运行服务器");
                break;
        }
    }
    $json["host"] = $properties["host"];
    $json["port"] = $properties["port"];
    $json["admin_group"] = $properties["admin_group"];
    $json["super_user"] = $properties["super_user"];
    $json["info_level"] = $properties["info_level"];
    file_put_contents(CONFIG_DIR . "config.json", json_encode($json, 128 | 256));
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