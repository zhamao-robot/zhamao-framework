<?php

declare(strict_types=1);

use ZM\Logger\ConsoleLogger;

/**
 * 以指定的日志等级运行回调
 *
 * @param callable $callback  执行回调
 * @param string   $log_level 日志等级
 */
function run_with_log(callable $callback, string $log_level = 'debug')
{
    $stash_logger = ob_logger();
    ob_logger_register(new ConsoleLogger($log_level));
    $callback();
    ob_logger_register($stash_logger);
}
