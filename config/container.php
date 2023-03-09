<?php

declare(strict_types=1);

use OneBot\Driver\Driver;
use OneBot\Driver\Process\ProcessManager;
use Psr\Log\LoggerInterface;
use ZM\Config\Environment;
use ZM\Config\EnvironmentInterface;
use ZM\Framework;
use ZM\HasRuntimeInfo;

/*
 * 这里是容器的配置文件，你可以在这里配置容器的绑定和其他一些参数。
 * 选用的容器是 PHP-DI，你可以在这里查看文档：https://php-di.org/doc/
 * 我们建议你在使用容器前先阅读以下章节：
 * - 基本使用方式：https://php-di.org/doc/container.html
 * - 注册绑定方式：https://php-di.org/doc/php-definitions.html#definition-types
 * - 最佳使用实践：https://php-di.org/doc/best-practices.html
 * 同时，你也可以选择查阅我们（炸毛框架）的相关文档，其会包含一些简单的使用方法和示例。
 * 关于框架在不同事件（或周期中）默认注册的绑定，请参阅我们的文档，或直接查看 {@see \ZM\Container\ContainerRegistrant}
 */
return [
    // 这里定义的是全局容器的绑定，不建议在此处直接调用框架、应用内部的类或方法，因为这些类可能还没有被加载或初始化
    // 你可以使用匿名函数来延迟加载
    'definitions' => [
        'worker_id' => fn () => ProcessManager::getProcessId(),
        Driver::class => fn () => Framework::getInstance()->getDriver(),
        LoggerInterface::class => fn () => logger(),
        EnvironmentInterface::class => Environment::class,

        HasRuntimeInfo::class => Framework::class,
    ],

    // 容器的缓存配置，默认情况下，只有在生产环境下才会启用缓存
    // 启用缓存后可以减少重复反射的开销，在首次解析后直接从缓存中读取
    // 此功能要求 APCu 扩展，如果你没有安装，将会输出警告并禁用缓存
    // 请在更新容器配置后手动执行 `apcu_clear_cache()` 来清除缓存
    // 详细介绍请参阅：https://php-di.org/doc/performances.html#caching
    'cache' => [
        // 是否启用缓存，支持 bool、callable
        'enable' => fn () => Framework::getInstance()->runtime_preferences->environment('production'),
        'namespace' => 'zm',
    ],
];
