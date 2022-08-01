<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use ZM\Logger\ConsoleLogger;

/**
 * 根据具体操作系统替换目录分隔符
 *
 * @param string $dir 目录
 */
function zm_dir(string $dir): string
{
    if (strpos($dir, 'phar://') === 0) {
        return $dir;
    }
    return str_replace('/', DIRECTORY_SEPARATOR, $dir);
}

/**
 * 获取内部错误码
 *
 * @param int|string $code
 */
function zm_internal_errcode($code): string
{
    return "[ErrCode:{$code}] ";
}

function zm_instance_id(): string
{
    if (defined('ZM_INSTANCE_ID')) {
        return ZM_INSTANCE_ID;
    }
    if (!defined('ZM_START_TIME')) {
        define('ZM_START_TIME', microtime(true));
    }
    $instance_id = dechex(crc32(strval(ZM_START_TIME)));
    define('ZM_INSTANCE_ID', $instance_id);
    return ZM_INSTANCE_ID;
}

/**
 * 助手方法，返回一个 Logger 实例
 */
function logger(): LoggerInterface
{
    global $ob_logger;
    if ($ob_logger === null) {
        return new ConsoleLogger();
    }
    return $ob_logger;
}

/**
 * 判断传入的数组是否为关联数组
 */
function is_assoc_array(array $array): bool
{
    return !empty($array) && array_keys($array) !== range(0, count($array) - 1);
}

/**
 * @return object
 *
 * TODO: 等待完善DI
 */
function resolve(string $class)
{
    return new $class();
}
