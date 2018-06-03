<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/3/29
 * Time: 11:13
 */

date_default_timezone_set("Asia/Shanghai");

//工作目录设置
define("WORKING_DIR", __DIR__ . "/");
echo "工作目录：".WORKING_DIR."\n";
define("CONFIG_DIR", WORKING_DIR . "config/");
define("USER_DIR", WORKING_DIR . "users");

//启动时间
define("START_TIME", time());
@mkdir(CONFIG_DIR, 0777, true);
@mkdir(USER_DIR, 0777, true);
register_shutdown_function('handleFatal');
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



$host = "0.0.0.0";
$api_host = "127.0.0.1";
$api_port = 10000;
$event_port = 20000;
$admin_group = "";
$info_level = 1;
$super_user = [];

if (!file_exists(CONFIG_DIR . "config.json")) {
    file_put_contents(CONFIG_DIR . "config.json", json_encode([]));
}
$json = json_decode(file_get_contents(CONFIG_DIR . "config.json"), true);
if (!isset($json["host"])) {
    echo "请输入你要监听的Event IP(默认0.0.0.0) ：";
    $r = strtolower(trim(fgets(STDIN)));
    if ($r == "") {
        echo "监听地址：0.0.0.0(默认)\n";
        $json["host"] = $host;
    } else {
        $host = $r;
        echo "监听地址：" . $r . "\n";
        $json["host"] = $host;
    }
} else {
    $host = $json["host"];
}
if (!isset($json["event_port"])) {
    a3:
    echo "请输入你要监听的Event端口(默认20000) ：";
    $r = strtolower(trim(fgets(STDIN)));
    if ($r == "") {
        echo "监听地址：20000(默认)\n";
        $json["event_port"] = $event_port;
    } else {
        if (!is_numeric($r)) {
            echo "输入错误！请输入数字！（1-65535）\n";
            goto a3;
        }
        $event_port = $r;
        echo "监听地址：" . $r . "\n";
        $json["event_port"] = $event_port;
    }
} else {
    $event_port = $json["event_port"];
}
if (!isset($json["api_host"])) {
    echo "请输入你要连接的api server IP(默认127.0.0.1) ：";
    $r = strtolower(trim(fgets(STDIN)));
    if ($r == "") {
        echo "API地址：127.0.0.1(默认)\n";
        $json["api_host"] = $api_host;
    } else {
        $api_host = $r;
        echo "监听地址：" . $r . "\n";
        $json["api_host"] = $api_host;
    }
} else {
    $api_host = $json["api_host"];
}
if (!isset($json["api_port"])) {
    a2:
    echo "请输入你要监听的API端口(默认10000) ：";
    $r = strtolower(trim(fgets(STDIN)));
    if ($r == "") {
        echo "监听地址：10000(默认)\n";
        $json["api_port"] = $api_port;
    } else {
        if (!is_numeric($r)) {
            echo "输入错误！请输入数字！（1-65535）\n";
            goto a2;
        }
        $api_port = $r;
        echo "监听地址：" . $r . "\n";
        $json["api_port"] = $api_port;
    }
} else {
    $api_port = $json["api_port"];
}
if (!isset($json["admin_group"])) {
    a4:
    echo "请输入你要设置的管理员群：";
    $r = strtolower(trim(fgets(STDIN)));
    if ($r == "") {
        echo "检测到你没有设置管理员群，本次跳过\n";
    } else {
        if (!is_numeric($r)) {
            echo "输入错误！请输入数字群号！\n";
            goto a4;
        }
        $admin_group = $r;
        echo "管理群：" . $r . "\n";
        $json["admin_group"] = $admin_group;
    }
} else {
    $admin_group = $json["admin_group"];
}

if (!isset($json["super_user"])) {
    a5:
    echo "请输入你要设置的高级管理员：";
    $r = strtolower(trim(fgets(STDIN)));
    if ($r == "") {
        echo "检测到你没有设置高级管理员，本次跳过\n";
    } else {
        if (!is_numeric($r)) {
            echo "输入错误！请输入数字QQ号！\n";
            goto a5;
        }
        $super_user[] = $r;
        echo "管理员：" . $r . "\n";
        $json["super_user"][] = $r;
    }
} else {
    $super_user = $json["super_user"];
}

file_put_contents(CONFIG_DIR."config.json", json_encode($json, 128 | 256));

//loading projects
require(WORKING_DIR . "src/cqbot/Framework.php");
require(WORKING_DIR . "src/cqbot/utils/Buffer.php");
require(WORKING_DIR . "src/cqbot/utils/ErrorStatus.php");
require(WORKING_DIR . "src/cqbot/utils/Console.php");

$cqbot = new Framework();
$cqbot->setHost($host);
$cqbot->setApiPort($api_port);
$cqbot->setEventPort($event_port);
$cqbot->setAdminGroup($admin_group);
$cqbot->setInfoLevel($info_level);
$cqbot->init($super_user);
$cqbot->eventServerStart();