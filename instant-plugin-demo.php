<?php

declare(strict_types=1);

/*
return function () {
    $plugin = new \ZM\Plugin\InstantPlugin(__DIR__);

    $cmd = \ZM\Annotation\OneBot\BotCommand::make(name: 'test', match: '测试')->withArgument(name: 'arg1')->withMethod(function () {
        ctx()->reply('test ok');
    });
    $event = BotEvent::make(type: 'message')->withMethod(function () {
    });
    $plugin->addBotEvent($event);
    $plugin->addBotCommand($cmd);

    $plugin->registerEvent(HttpRequestEvent::getName(), function (HttpRequestEvent $event) {
        $event->withResponse(\OneBot\Http\HttpFactory::getInstance()->createResponse(503));
    });
    return $plugin;
};
*/
