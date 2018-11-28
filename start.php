<?php

//initialize settings.
define("WORKING_DIR", __DIR__ . "/");
include(WORKING_DIR."src/framework/global_functions.php");
define("CQ_DATA", WORKING_DIR . settings()["cq_data"]);
define("CONFIG_DIR", WORKING_DIR . settings()["config_dir"]);
define("USER_DIR", WORKING_DIR . settings()["user_dir"]);
define("CRASH_DIR", WORKING_DIR . settings()["crash_dir"]);
@mkdir(CQ_DATA, 0777, true);
@mkdir(CONFIG_DIR, 0777, true);
@mkdir(USER_DIR, 0777, true);
@mkdir(CRASH_DIR, 0777, true);

spl_autoload_register("class_loader");

if (settings() === null) die("Start failed. Please check cqbot.json file.\n");

//启动时间
define("START_TIME", time());

//设置时区（如果非国内用户请自行注释或更改）
date_default_timezone_set("Asia/Shanghai");

//如果是第一次启动或监听地址为空，则启动配置向导
if (settings()["swoole_host"] == "" || settings()["swoole_port"] == "") include("wizard.php");

//initializing framework
(new Framework(settings()))->start();

