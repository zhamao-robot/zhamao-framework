<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Logger\ConsoleLogger;

class RegisterLogger
{
    public function bootstrap(array $config): void
    {
        // 初始化 Logger
        if (!ob_logger_registered()) {
            $debug = $config['verbose'] ?? false;
            $debug = $debug ? 'debug' : null;
            // 如果没有注册过 Logger，那么就初始化一个，在启动框架前注册的话，就不会初始化了，可替换为其他 Logger
            $logger = new ConsoleLogger($config['log-level'] ?? $debug ?? 'info');
            ob_logger_register($logger);
        }
    }
}
