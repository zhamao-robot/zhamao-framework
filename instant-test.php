<?php

require_once "vendor/autoload.php";

use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\Swoole\OnOpenEvent;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Console\Console;
use ZM\Module\InstantModule;
use ZM\ZMServer;

$weather = new InstantModule("weather");


$weather->onEvent(OnOpenEvent::class, ['connect_type' => 'qq'], function(ConnectionObject $conn) {
    Console::info("机器人 " . $conn->getOption("connect_id") . " 已连接！");
});

$weather->onEvent(CQCommand::class, ['match' => '你好'], function() {
    ctx()->reply("hello呀！");
});

$app = new ZMServer("app-name");
$app->addModule($weather);
$app->run();