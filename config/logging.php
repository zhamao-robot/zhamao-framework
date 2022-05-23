<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ZM\Logger\ConsoleLogger;

return [
    'level' => LogLevel::DEBUG,
    'logger' => static function (string $title = null): LoggerInterface {
        if ($title) {
            $title = strtoupper($title);
        } else {
            // 在 Master 中，worker_id 将不存在
            $title = app()->has('worker_id') ? '#' . app('worker_id') : 'MST';
        }

        $logger = new ConsoleLogger(zm_config('logging.level'));
        $logger::$format = "[%date%] [%level%] [{$title}] %body%";
        $logger::$date_format = 'Y-m-d H:i:s';
        // 如果你喜欢旧版的日志格式，请取消下行注释
//        $logger::$date_format = 'm-d H:i:s';
        return $logger;
    },
];
