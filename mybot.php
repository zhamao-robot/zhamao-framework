<?php

declare(strict_types=1);

use OneBot\Driver\Event\Http\HttpRequestEvent;

require 'vendor/autoload.php';

// 创建框架 App
$app = new ZM\ZMApplication();
// 传入自定义配置文件
$app->patchConfig([
    'driver' => 'workerman',
]);
// 改变启动所需的参数
$app->patchArgs([
    '--private-mode',
]);
// 如果有 Composer 依赖的插件，使用 enablePlugins 进行开启
$app->enablePlugins([
    'a',
    'b',
    'c',
    'd',
]);
// BotCommand 事件构造
$cmd = BotCommand::make('test')->on(function () {
    ctx()->reply('test ok');
});
$event = BotEvent::make(type: 'message')->on(function () {
});
$app->addBotEvent($event);
$app->addBotCommand($cmd);

$app->addEvent(HttpRequestEvent::getName(), function (HttpRequestEvent $event) {
    $event->withResponse(\OneBot\Http\HttpFactory::getInstance()->createResponse(503));
});

$app->run();
