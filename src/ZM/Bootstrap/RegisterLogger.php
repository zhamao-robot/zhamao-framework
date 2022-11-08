<?php

namespace ZM\Bootstrap;

use ZM\Logger\ConsoleLogger;

class RegisterLogger
{
    public function bootstrap(array $config): void
    {
        // 初始化 Logger
        if (!ob_logger_registered()) {
            // 如果没有注册过 Logger，那么就初始化一个，在启动框架前注册的话，就不会初始化了，可替换为其他 Logger
            $logger = new ConsoleLogger($config['log-level'] ?? 'info');
            ob_logger_register($logger);
        }
    }
}
