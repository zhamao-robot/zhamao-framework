<?php

declare(strict_types=1);

use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Coroutine\CoroutineInterface;
use OneBot\Driver\Process\ExecutionResult;
use OneBot\V12\Object\MessageSegment;
use Psr\Log\LoggerInterface;
use ZM\Config\ZMConfig;
use ZM\Container\ContainerHolder;
use ZM\Logger\ConsoleLogger;
use ZM\Middleware\MiddlewareHandler;
use ZM\Store\Database\DBException;
use ZM\Store\Database\DBWrapper;

// 防止重复引用引发报错
if (function_exists('zm_internal_errcode')) {
    return;
}

/**
 * 根据具体操作系统替换目录分隔符
 *
 * @param string $dir 目录
 */
function zm_dir(string $dir): string
{
    if (str_starts_with($dir, 'phar://')) {
        return $dir;
    }
    return str_replace('/', DIRECTORY_SEPARATOR, $dir);
}

/**
 * 执行shell指令
 *
 * @param string $cmd 命令行
 */
function zm_exec(string $cmd): ExecutionResult
{
    return Adaptive::exec($cmd);
}

/**
 * sleep 指定时间，单位为秒（最小单位为1毫秒，即0.001）
 */
function zm_sleep(float|int $time)
{
    Adaptive::sleep($time);
}

/**
 * 获取协程接口
 */
function coroutine(): ?CoroutineInterface
{
    return Adaptive::getCoroutine();
}

/**
 * 获取内部错误码
 */
function zm_internal_errcode(int|string $code): string
{
    return "[ErrCode:{$code}] ";
}

/**
 * 返回当前炸毛实例的 ID
 */
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
 * 格式匹配
 */
function match_pattern(string $pattern, string $subject): bool
{
    $pattern = str_replace(['\*', '\\\\.*'], ['.*', '\*'], preg_quote($pattern, '/'));
    $pattern = '/^' . $pattern . '$/i';
    return preg_match($pattern, $subject) === 1;
}

/**
 * 匹配参数
 *
 * @return array|false 成功时返回匹配到的参数数组，失败时返回false
 */
function match_args(string $pattern, string $subject)
{
    $result = [];
    if (match_pattern($pattern, $subject)) {
        if (mb_strpos($pattern, '*') === false) {
            return [];
        }
        $exp = explode('*', $pattern);
        $i = 0;
        foreach ($exp as $k => $v) {
            if (empty($v) && $k === 0) {
                continue;
            }
            if (empty($v) && $k === count($exp) - 1) {
                $subject .= '^EOL';
                $v = '^EOL';
            }
            $cur_var = '';
            $ori = $i;
            while (($a = mb_substr($subject, $i, mb_strlen($v))) !== $v && !empty($a)) {
                $cur_var .= mb_substr($subject, $i, 1);
                ++$i;
            }
            if ($i !== $ori || $k === 1 || $k === count($exp) - 1) {
                $result[] = $cur_var;
            }
            $i += mb_strlen($v);
        }
        return $result;
    }
    return false;
}

/**
 * 构建消息段的助手函数
 *
 * @param string $type 类型
 * @param array  $data 字段
 */
function segment(string $type, array $data = []): MessageSegment
{
    return new MessageSegment($type, $data);
}

/**
 * 中间件操作类的助手函数
 */
function middleware(): MiddlewareHandler
{
    return MiddlewareHandler::getInstance();
}

// ////////////////// 容器部分 //////////////////////

/**
 * 获取容器实例
 */
function container(): DI\Container
{
    return ContainerHolder::getEventContainer();
}

/**
 * 解析类实例（使用容器）
 *
 * 这是 {@see container()}->make($abstract, $parameters) 的别名
 *
 * @template     T
 * @param  class-string<T> $abstract
 * @return Closure|mixed|T
 */
function resolve(string $abstract, array $parameters = [])
{
    /* @noinspection PhpUnhandledExceptionInspection */
    return container()->make($abstract, $parameters);
}

/**
 * 获取 MySQL 调用的类
 *
 * @throws DBException
 */
function db(string $name = '')
{
    return new DBWrapper($name);
}

/**
 * 获取构建 MySQL 的类
 *
 * @throws DBException
 */
function sql_builder(string $name = '')
{
    return (new DBWrapper($name))->createQueryBuilder();
}

/**
 * 获取 / 设置配置项
 *
 * 传入键名和（或）默认值，获取配置项
 * 传入数组，设置配置项
 * 不传参数，返回配置容器
 *
 * @param  null|array|string   $key     键名
 * @param  null|mixed          $default 默认值
 * @return mixed|void|ZMConfig
 */
function config(array|string $key = null, mixed $default = null)
{
    $config = ZMConfig::getInstance();
    if (is_null($key)) {
        return $config;
    }
    if (is_array($key)) {
        $config->set($key);
        return;
    }
    return $config->get($key, $default);
}

function bot(): ZM\Context\BotContext
{
    if (container()->has(ZM\Context\BotContext::class)) {
        return container()->get(ZM\Context\BotContext::class);
    }
    return new \ZM\Context\BotContext('', '');
}
