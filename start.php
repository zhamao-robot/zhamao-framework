<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/3/29
 * Time: 11:13
 */

namespace {

}

namespace cqbot {
    date_default_timezone_set("Asia/Shanghai");
    define("WORKING_DIR", __DIR__ . "/");
    define("CONFIG_DIR", WORKING_DIR . "config/");
    define("USER_DIR", WORKING_DIR . "users");
    define("START_TIME", time());
    @mkdir(CONFIG_DIR, 0777, true);
    @mkdir(USER_DIR, 0777, true);
    register_shutdown_function('handleFatal');

    function handleFatal(){
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

    function CQMsg($msg, $type, $id){
        if ($type === "group") {
            $reply = ["action" => "send_group_msg", "params" => ["group_id" => $id, "message" => $msg]];
            $reply["echo"] = $reply;
            $reply["echo"]["time"] = time();
            $reply = json_encode($reply);
        }
        else if ($type === "private") {
            $reply = ["action" => "send_private_msg", "params" => ["user_id" => $id, "message" => $msg]];
            $reply["echo"] = $reply;
            $reply["echo"]["time"] = time();
            $reply = json_encode($reply);
        }
        else if ($type === "discuss") {
            $reply = ["action" => "send_discuss_msg", "params" => ["discuss_id" => $id, "message" => $msg]];
            $reply["echo"] = $reply;
            $reply["echo"]["time"] = time();
            $reply = json_encode($reply);
        }
        else {
            $reply = false;
        }
        return $reply;
    }

    //loading projects
    require(WORKING_DIR . "src/cqbot/Framework.php");
    require(WORKING_DIR . "src/cqbot/utils/Buffer.php");
    require(WORKING_DIR . "src/cqbot/utils/ErrorStatus.php");
    require(WORKING_DIR . "src/cqbot/utils/Console.php");
    $cqbot = new Framework();
    $cqbot->setHost("127.0.0.1");
    $cqbot->setApiPort("10000");
    $cqbot->setEventPort("20000");
    $cqbot->setAdminGroup("");
    $cqbot->setInfoLevel(1);
    $cqbot->init();
    $cqbot->eventServerStart();
}