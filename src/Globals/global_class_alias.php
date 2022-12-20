<?php

declare(strict_types=1);

use ZM\Container\ClassAliasHelper;

ClassAliasHelper::addAlias(\ZM\Annotation\Framework\BindEvent::class, 'BindEvent');
ClassAliasHelper::addAlias(\ZM\Annotation\Framework\Init::class, 'Init');
ClassAliasHelper::addAlias(\ZM\Annotation\Framework\Setup::class, 'Setup');
ClassAliasHelper::addAlias(\ZM\Annotation\Http\Controller::class, 'Controller');
ClassAliasHelper::addAlias(\ZM\Annotation\Http\Route::class, 'Route');
ClassAliasHelper::addAlias(\ZM\Annotation\Middleware\Middleware::class, 'Middleware');
ClassAliasHelper::addAlias(\ZM\Annotation\OneBot\BotCommand::class, 'BotCommand');
ClassAliasHelper::addAlias(\ZM\Annotation\OneBot\BotEvent::class, 'BotEvent');
ClassAliasHelper::addAlias(\ZM\Annotation\OneBot\CommandArgument::class, 'CommandArgument');
ClassAliasHelper::addAlias(\ZM\Annotation\Closed::class, 'Closed');
ClassAliasHelper::addAlias(\ZM\Plugin\ZMPlugin::class, 'ZMPlugin');

// 下面是 OneBot 相关类的全局别称
ClassAliasHelper::addAlias(\OneBot\Driver\Event\WebSocket\WebSocketOpenEvent::class, 'WebSocketOpenEvent');
ClassAliasHelper::addAlias(\OneBot\Driver\Event\WebSocket\WebSocketCloseEvent::class, 'WebSocketCloseEvent');
ClassAliasHelper::addAlias(\OneBot\Driver\Event\WebSocket\WebSocketMessageEvent::class, 'WebSocketMessageEvent');
ClassAliasHelper::addAlias(\OneBot\Driver\Event\Http\HttpRequestEvent::class, 'HttpRequestEvent');
