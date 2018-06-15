<?php

//工作目录设置
define("WORKING_DIR", __DIR__ . "/");
define("CONFIG_DIR", WORKING_DIR . "config/");
define("USER_DIR", WORKING_DIR . "users");

require("tools.php");

//loading projects
require(WORKING_DIR . "src/cqbot/Framework.php");
require(WORKING_DIR . "src/cqbot/utils/Buffer.php");
require(WORKING_DIR . "src/cqbot/utils/ErrorStatus.php");
require(WORKING_DIR . "src/cqbot/utils/Console.php");

//初始参数设置：host、端口、多个机器人号对应的admin_group、事件等级、多个机器人号对应的超级管理员
$properties["host"] = "0.0.0.0";
$properties["port"] = 20000;
$properties["admin_group"] = [];
$properties["info_level"] = 1;
$properties["super_user"] = [];

$json = json_decode(file_get_contents(CONFIG_DIR . "config.json"), true);

if (!isset($json["host"]) || !isset($json["port"])) setupWizard($json, $properties);

//initializing framework
$cqbot = new Framework();
$cqbot->setHost($properties["host"]);
$cqbot->setEventPort($properties["port"]);
$cqbot->setAdminGroup($properties["admin_group"]);
$cqbot->setInfoLevel($properties["info_level"]);
$cqbot->setSuperUser($properties["super_user"]);
$cqbot->init();
$cqbot->eventServerStart();