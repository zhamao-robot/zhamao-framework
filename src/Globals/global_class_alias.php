<?php

declare(strict_types=1);

class_alias(\ZM\Annotation\Framework\BindEvent::class, 'BindEvent');
class_alias(\ZM\Annotation\Framework\Cron::class, 'Cron');
class_alias(\ZM\Annotation\Framework\Init::class, 'Init');
class_alias(\ZM\Annotation\Framework\Setup::class, 'Setup');
class_alias(\ZM\Annotation\Framework\Tick::class, 'Tick');

class_alias(\ZM\Annotation\Http\Controller::class, 'Controller');
class_alias(\ZM\Annotation\Http\Route::class, 'Route');

class_alias(\ZM\Annotation\Middleware\Middleware::class, 'Middleware');

class_alias(\ZM\Annotation\OneBot\BotAction::class, 'BotAction');
class_alias(\ZM\Annotation\OneBot\BotActionResponse::class, 'BotActionResponse');
class_alias(\ZM\Annotation\OneBot\BotCommand::class, 'BotCommand');
class_alias(\ZM\Annotation\OneBot\BotEvent::class, 'BotEvent');
class_alias(\ZM\Annotation\OneBot\CommandArgument::class, 'CommandArgument');
class_alias(\ZM\Annotation\OneBot\CommandHelp::class, 'CommandHelp');

class_alias(\ZM\Annotation\Closed::class, 'Closed');

class_alias(\ZM\Middleware\MiddlewareArgTrait::class, 'MiddlewareArgTrait');
class_alias(\ZM\Middleware\Pipeline::class, 'Pipeline');

class_alias(\ZM\Plugin\ZMPlugin::class, 'ZMPlugin');
class_alias(\ZM\Context\BotContext::class, 'BotContext');
class_alias(\ZM\Utils\ZMRequest::class, 'ZMRequest');
class_alias(\ZM\Store\KV\LightCache::class, 'LightCache');
class_alias(\ZM\Store\KV\Redis\KVRedis::class, 'KVRedis');

// 下面是 OneBot 相关类的全局别称
class_alias(\OneBot\Driver\Event\WebSocket\WebSocketOpenEvent::class, 'WebSocketOpenEvent');
class_alias(\OneBot\Driver\Event\WebSocket\WebSocketCloseEvent::class, 'WebSocketCloseEvent');
class_alias(\OneBot\Driver\Event\WebSocket\WebSocketMessageEvent::class, 'WebSocketMessageEvent');
class_alias(\OneBot\Driver\Event\Http\HttpRequestEvent::class, 'HttpRequestEvent');

class_alias(\OneBot\V12\Object\OneBotEvent::class, 'OneBotEvent');

// 下面是 Choir 相关的全局别称
class_alias(\Choir\Http\HttpFactory::class, 'HttpFactory');
