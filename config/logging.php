<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ZM\Logger\ConsoleLogger;

return [
    // 设置默认的log等级
    'level' => LogLevel::INFO,
    // logger自定义回调
    'logger' => static function (string $prefix = null): LoggerInterface {
        if ($prefix) {
            $prefix = strtoupper($prefix);
        } else {
            // 在 Master 中，worker_id 将不存在
            $prefix = app()->has('worker_id') ? '#' . app('worker_id') : 'MST';
        }

        $logger = new ConsoleLogger(config('logging.level'));
        $logger::$format = "[%date%] [%level%] [{$prefix}] %body%";
        $logger::$date_format = 'Y-m-d H:i:s';
        // 如果你喜欢旧版的日志格式，请取消下行注释
//        $logger::$date_format = 'm-d H:i:s';
        return $logger;
    },
];
