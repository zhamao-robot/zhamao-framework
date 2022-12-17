<?php

declare(strict_types=1);

$plugin = new ZMPlugin(__DIR__);

/*
 * 发送 "测试 123"，回复 "你好，123"
 */
$cmd1 = BotCommand::make('test', '测试')->withArgument('arg1')->on(fn () => '你好，{{arg1}}');

/*
 * 浏览器访问 http://ip:port/index233，返回内容
 */
$route1 = Route::make('/index233')->on(fn () => '<h1>Hello world</h1>');

$plugin->addBotCommand($cmd1);
$plugin->addHttpRoute($route1);

return [
    'plugin-name' => 'pasd',
    'version' => '1.0.0',
    'plugin' => $plugin,
];
