<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ZM\Logger\ConsoleLogger;

return [
    'level' => LogLevel::DEBUG,
    'logger' => static function (): LoggerInterface {
        $worker_id = app('worker_id');

        $logger = new ConsoleLogger(zm_config('logging.level'));
        $logger::$format = "[%date%] [%level%] [#{$worker_id}] %body%";
        $logger::$date_format = 'Y-m-d H:i:s';
        // 如果你喜欢旧版的日志格式，请取消下行注释
//        $logger::$date_format = 'm-d H:i:s';
        return $logger;
    },
];
