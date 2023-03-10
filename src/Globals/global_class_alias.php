<?php

declare(strict_types=1);

// Framework 类注解
class_alias(\ZM\Annotation\Framework\BindEvent::class, 'BindEvent');
class_alias(\ZM\Annotation\Framework\Cron::class, 'Cron');
class_alias(\ZM\Annotation\Framework\Init::class, 'Init');
class_alias(\ZM\Annotation\Framework\Setup::class, 'Setup');
class_alias(\ZM\Annotation\Framework\Tick::class, 'Tick');

// Http 类注解
class_alias(\ZM\Annotation\Http\Controller::class, 'Controller');
class_alias(\ZM\Annotation\Http\Route::class, 'Route');

// Middleware 类注解
class_alias(\ZM\Annotation\Middleware\Middleware::class, 'Middleware');

// OneBot 类注解
class_alias(\ZM\Annotation\OneBot\BotAction::class, 'BotAction');
class_alias(\ZM\Annotation\OneBot\BotActionResponse::class, 'BotActionResponse');
class_alias(\ZM\Annotation\OneBot\BotCommand::class, 'BotCommand');
class_alias(\ZM\Annotation\OneBot\BotEvent::class, 'BotEvent');
class_alias(\ZM\Annotation\OneBot\CommandArgument::class, 'CommandArgument');
class_alias(\ZM\Annotation\OneBot\CommandHelp::class, 'CommandHelp');

// 全局注解
class_alias(\ZM\Annotation\Closed::class, 'Closed');

// 中间件类
class_alias(\ZM\Middleware\MiddlewareArgTrait::class, 'MiddlewareArgTrait');
class_alias(\ZM\Middleware\MiddlewareHandler::class, 'MiddlewareHandler');
class_alias(\ZM\Middleware\NeedAnnotationTrait::class, 'NeedAnnotationTrait');
class_alias(\ZM\Middleware\Pipeline::class, 'Pipeline');
class_alias(\ZM\Middleware\TimerMiddleware::class, 'TimerMiddleware');
class_alias(\ZM\Middleware\WebSocketFilter::class, 'WebSocketFilter');

// 插件、上下文、工具类
class_alias(\ZM\Plugin\ZMPlugin::class, 'ZMPlugin');
class_alias(\ZM\Context\BotContext::class, 'BotContext');
class_alias(\ZM\Utils\CatCode::class, 'CatCode');
class_alias(\ZM\Utils\ConnectionUtil::class, 'ConnectionUtil');
class_alias(\ZM\Utils\MessageUtil::class, 'MessageUtil');
class_alias(\ZM\Utils\OneBot12FileDownloader::class, 'OneBot12FileDownloader');
class_alias(\ZM\Utils\OneBot12FileUploader::class, 'OneBot12FileUploader');
class_alias(\ZM\Utils\ZMRequest::class, 'ZMRequest');
class_alias(\ZM\Utils\ZMUtil::class, 'ZMUtil');
class_alias(\ZM\Store\KV\LightCache::class, 'LightCache');
class_alias(\ZM\Store\KV\Redis\KVRedis::class, 'KVRedis');
class_alias(\ZM\Config\ZMConfig::class, 'ZMConfig');

// 下面是 OneBot 相关类的全局别称
class_alias(\OneBot\Driver\Event\WebSocket\WebSocketOpenEvent::class, 'WebSocketOpenEvent');
class_alias(\OneBot\Driver\Event\WebSocket\WebSocketCloseEvent::class, 'WebSocketCloseEvent');
class_alias(\OneBot\Driver\Event\WebSocket\WebSocketMessageEvent::class, 'WebSocketMessageEvent');
class_alias(\OneBot\Driver\Event\Http\HttpRequestEvent::class, 'HttpRequestEvent');

// OneBot 12 的对象
class_alias(\OneBot\V12\Object\OneBotEvent::class, 'OneBotEvent');
class_alias(\OneBot\V12\Object\Action::class, 'Action');

// 下面是 Choir 相关的全局别称
class_alias(\Choir\Http\HttpFactory::class, 'HttpFactory');
class_alias(\Choir\WebSocket\FrameInterface::class, 'FrameInterface');

// PSR 接口的别名
class_alias(\Psr\Http\Message\ServerRequestInterface::class, 'ServerRequestInterface');
class_alias(\Psr\Http\Message\RequestInterface::class, 'RequestInterface');
class_alias(\Psr\Http\Message\ResponseInterface::class, 'ResponseInterface');
class_alias(\Psr\Http\Message\UriInterface::class, 'UriInterface');
class_alias(\Psr\Log\LoggerInterface::class, 'LoggerInterface');
class_alias(\Psr\Container\ContainerInterface::class, 'ContainerInterface');
