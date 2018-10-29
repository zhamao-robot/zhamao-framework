<?php

function loadAllClass($dir) {
    $dir_obj = scandir($dir);
    unset($dir_obj[0], $dir_obj[1]);
    foreach ($dir_obj as $m) {
        $taskFileName = explode(".", $m);
        if (is_dir($dir . $m . "/")) loadAllClass($dir . $m . "/");
        else {
            if (count($taskFileName) < 2 || ($taskFileName[1] != "php" && $taskFileName[1] != "phar")) continue;
            require_once($dir . $m);
        }
    }
}

//工作目录设置
define("WORKING_DIR", __DIR__ . "/");

function settings() { return json_decode(file_get_contents(WORKING_DIR . "/cqbot.json"), true); }

if (settings() === null) die("Start failed. Please check cqbot.json file.\n");

//启动时间
define("START_TIME", time());

//设置dir常量
define("CQ_DATA", WORKING_DIR . settings()["cq_data"]);
define("CONFIG_DIR", WORKING_DIR . settings()["config_dir"]);
define("USER_DIR", WORKING_DIR . settings()["user_dir"]);
define("CRASH_DIR", WORKING_DIR . settings()["crash_dir"]);
@mkdir(CQ_DATA, 0777, true);
@mkdir(CONFIG_DIR, 0777, true);
@mkdir(USER_DIR, 0777, true);
@mkdir(CRASH_DIR, 0777, true);

//设置时区（如果非国内用户请自行注释或更改）
date_default_timezone_set("Asia/Shanghai");

//加载框架
require(WORKING_DIR . "src/framework/loader.php");

//加载全局函数
require("tools.php");


//如果是第一次启动或监听地址为空，则启动配置向导
if (settings()["host"] == "" || settings()["port"] == "") include("wizard.php");

//initializing framework
$cqbot = new Framework(settings());
$cqbot->start();