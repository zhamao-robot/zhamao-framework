<?php

declare(strict_types=1);

use Swoole\Atomic;
use Swoole\Coroutine;
use Swoole\Coroutine as Co;
use Swoole\Coroutine\System;
use Swoole\WebSocket\Server;
use Symfony\Component\VarDumper\VarDumper;
use ZM\API\OneBotV11;
use ZM\API\ZMRobot;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Context\Context;
use ZM\Context\ContextInterface;
use ZM\Event\EventManager;
use ZM\Exception\RobotNotFoundException;
use ZM\Exception\ZMKnownException;
use ZM\Framework;
use ZM\Store\LightCacheInside;
use ZM\Store\ZMAtomic;
use ZM\Store\ZMBuf;
use ZM\Utils\DataProvider;

/**
 * 获取类路径
 */
function get_class_path(string $class_name): ?string
{
    $dir = str_replace('\\', '/', $class_name);
    $dir = DataProvider::getSourceRootDir() . '/src/' . $dir . '.php';
    $dir = str_replace('\\', '/', $dir);
    if (file_exists($dir)) {
        return $dir;
    }
    return null;
}

/**
 * 检查炸毛框架运行的环境
 * @internal
 */
function _zm_env_check()
{
    if (!extension_loaded('swoole')) {
        exit(zm_internal_errcode('E00001') . "Can not find swoole extension.\n");
    }
    if (version_compare(SWOOLE_VERSION, '4.5.0') === -1) {
        exit(zm_internal_errcode('E00002') . 'You must install swoole version >= 4.5.0 !');
    }
    if (version_compare(PHP_VERSION, '7.2') === -1) {
        exit(zm_internal_errcode('E00003') . 'PHP >= 7.2 required.');
    }
    if (version_compare(SWOOLE_VERSION, '4.6.7') < 0 && !extension_loaded('pcntl')) {
        Console::error(zm_internal_errcode('E00004') . 'Swoole 版本必须不低于 4.6.7 或 PHP 安装加载了 pcntl 扩展！');
        exit();
    }
}

/**
 * 分割消息字符串
 *
 * @param array $includes 需要进行切割的字符串，默认包含空格及制表符（\t)
 */
function explode_msg(string $msg, array $includes = [' ', "\t"]): array
{
    $msg = trim($msg);
    foreach ($includes as $v) {
        $msg = str_replace($v, "\n", $msg);
    }
    $msg_seg = explode("\n", $msg);
    $ls = [];
    foreach ($msg_seg as $v) {
        if (empty(trim($v))) {
            continue;
        }
        $ls[] = trim($v);
    }
    return $ls;
}

/**
 * 解码Unicode字符串
 */
function unicode_decode(string $str): ?string
{
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', static function ($matches) {
        return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
    }, $str);
}

/**
 * 格式匹配
 */
function match_pattern(string $pattern, string $subject): bool
{
    if (mb_strpos($pattern, '') === 0 && mb_strpos($subject, '') === 0) {
        return true;
    }
    if (mb_strpos($pattern, '*') === 0 && mb_substr($pattern, 1, 1) !== '' && mb_strpos($subject, '') === 0) {
        return false;
    }
    if (mb_strpos($pattern, mb_substr($subject, 0, 1)) === 0) {
        return match_pattern(mb_substr($pattern, 1), mb_substr($subject, 1));
    }
    if (mb_strpos($pattern, '*') === 0) {
        return match_pattern(mb_substr($pattern, 1), $subject) || match_pattern($pattern, mb_substr($subject, 1));
    }
    return false;
}

function split_explode(string $del, string $string, bool $divide_en = false): array
{
    $str = explode($del, $string);
    for ($i = 0, $i_max = mb_strlen($str[0]); $i < $i_max; ++$i) {
        if (
            is_numeric(mb_substr($str[0], $i, 1))
            && (
                !is_numeric(mb_substr($str[0], $i - 1, 1))
                && mb_substr($str[0], $i - 1, 1) !== ' '
                && ctype_alpha(mb_substr($str[0], $i - 1, 1)) === false
            )
        ) {
            $str[0] = mb_substr($str[0], 0, $i) . ' ' . mb_substr($str[0], $i);
        } elseif (
            $divide_en
            && mb_substr($str[0], $i - 1, 1) !== ' '
            && ctype_alnum(mb_substr($str[0], $i, 1))
            && !ctype_alnum(mb_substr($str[0], $i - 1, 1))
        ) {
            $str[0] = mb_substr($str[0], 0, $i) . ' ' . mb_substr($str[0], $i);
        }
    }
    $str = implode($del, $str);

    $ls = [];
    foreach (explode($del, $str) as $v) {
        if (empty(trim($v))) {
            continue;
        }
        $ls[] = $v;
    }
    return $ls ?: [''];
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
 * 判断当前连接类型是否为传入的$type
 *
 * @param  string           $type 连接类型
 * @throws ZMKnownException
 */
function current_connection_is(string $type): bool
{
    return ctx()->getConnection()->getName() === $type;
}

/**
 * 获取触发当前方法的注解
 */
function get_annotations(): array
{
    $s = debug_backtrace()[1];
    $list = [];
    foreach (EventManager::$events as $v) {
        foreach ($v as $vs) {
            if ($vs->class === $s['class'] && $vs->method === $s['function']) {
                $list[get_class($vs)][] = $vs;
            }
        }
    }
    return $list;
}

/**
 * 设置协程参数
 */
function set_coroutine_params(array $params): void
{
    $cid = Co::getCid();
    if ($cid === -1) {
        exit(zm_internal_errcode('E00061') . 'Cannot set coroutine params at none coroutine mode.');
    }
    if (isset(Context::$context[$cid])) {
        Context::$context[$cid] = array_merge(Context::$context[$cid], $params);
    } else {
        Context::$context[$cid] = $params;
    }
    foreach (Context::$context as $c => $v) {
        if (!Co::exists($c)) {
            unset(Context::$context[$c], ZMBuf::$context_class[$c]);
        }
    }
}

/**
 * 获取当前上下文
 *
 * @throws ZMKnownException
 */
function context(): ContextInterface
{
    return ctx();
}

/**
 * 获取当前上下文
 *
 * @throws ZMKnownException
 */
function ctx(): ContextInterface
{
    $cid = Co::getCid();
    $c_class = ZMConfig::get('global', 'context_class');
    if (isset(Context::$context[$cid])) {
        return ZMBuf::$context_class[$cid] ?? (ZMBuf::$context_class[$cid] = new $c_class($cid));
    }
    Console::debug("未找到当前协程的上下文({$cid})，正在找父进程的上下文");
    while (($parent_cid = Co::getPcid($cid)) !== -1) {
        $cid = $parent_cid;
        if (isset(Context::$context[$cid])) {
            return ZMBuf::$context_class[$cid] ?? (ZMBuf::$context_class[$cid] = new $c_class($cid));
        }
    }
    throw new ZMKnownException(zm_internal_errcode('E00072') . 'Unable to find context environment');
}

/**
 * 根据消息类型获取对应的OneBot目标名称
 *
 * @return string 如传入的消息类型不被支持，将默认返回`user_id`
 */
function get_onebot_target_id_name(string $message_type): string
{
    switch ($message_type) {
        case 'group':
            return 'group_id';
        case 'discuss':
            return 'discuss_id';
        case 'private':
        default:
            return 'user_id';
    }
}

/**
 * 协程休眠
 *
 * 与 {@link sleep()} 一致，只是增加了协程支持
 *
 * @since 2.7.3 此函数不再返回 true
 */
function zm_sleep(int $seconds = 1): void
{
    if (Coroutine::getCid() !== -1) {
        System::sleep($seconds);
    } else {
        usleep($seconds * 1000 * 1000);
    }
}

/**
 * 协程执行命令
 *
 * 与 {@link exec()} 一致，只是增加了协程支持
 *
 * @return array{code: int, signal: int, output: string}
 */
function zm_exec(string $command): array
{
    return System::exec($command);
}

/**
 * 获取当前协程ID
 *
 * 与 {@link Co::getCid()} 一致
 */
function zm_cid(): int
{
    return Co::getCid();
}

/**
 * 挂起当前协程
 *
 * 与 {@link Co::yield()} 一致
 */
function zm_yield()
{
    Co::yield();
}

/**
 * 恢复并继续执行指定协程
 *
 * 与 {@link Co::resume()} 一致
 */
function zm_resume(int $cid)
{
    Co::resume($cid);
}

/**
 * 指定延迟后执行函数
 *
 * @param int $delay 延迟时间，单位毫秒ms
 */
function zm_timer_after(int $delay, callable $runnable)
{
    Swoole\Timer::after($delay, static function () use ($runnable) {
        call_with_catch($runnable);
    });
}

/**
 * 重复在指定时间间隔后执行函数
 *
 * @param  int       $interval 间隔时间，单位毫秒ms
 * @return false|int 定时器ID，失败返回false
 */
function zm_timer_tick(int $interval, callable $runnable)
{
    if (zm_cid() === -1) {
        return go(static function () use ($interval, $runnable) {
            Console::debug('Adding extra timer tick of ' . $interval . ' ms');
            Swoole\Timer::tick($interval, static function () use ($runnable) {
                call_with_catch($runnable);
            });
        });
    }

    return Swoole\Timer::tick($interval, static function () use ($runnable) {
        call_with_catch($runnable);
    });
}

/**
 * 执行函数并记录异常
 */
function call_with_catch(callable $callable): void
{
    try {
        $callable();
    } catch (Exception $e) {
        $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
        Console::error(zm_internal_errcode('E00033') . 'Uncaught exception ' . get_class($e) . ': ' . $error_msg);
        Console::trace();
    } catch (Error $e) {
        $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
        Console::error(zm_internal_errcode('E00033') . 'Uncaught ' . get_class($e) . ': ' . $error_msg);
        Console::trace();
    }
}

/**
 * 生成消息的哈希值
 */
function hash_message(array $message): string
{
    return md5($message['user_id'] . '^' . $message['self_id'] . '^' . $message['message_type'] . '^' . ($message[$message['message_type'] . '_id'] ?? $message['user_id']));
}

/**
 * 获取 Swoole Server 实例
 *
 * 与 {@link Framework::$server} 一致
 */
function server(): ?Server
{
    return Framework::$server;
}

/**
 * 获取缓存当前框架pid的临时目录
 * @internal
 */
function _zm_pid_dir(): string
{
    global $_ZM_HASH;
    if (!isset($_ZM_HASH)) {
        $_ZM_HASH = md5(DataProvider::getWorkingDir());
    }
    return '/tmp/.zm_' . $_ZM_HASH;
}

/**
 * 获取 ZMRobot 实例
 *
 * 随机返回一个 ZMRobot 实例，效果等同于 {@link ZMRobot::getRandom()}。
 *
 * 在单机器人模式下，会直接返回该机器人实例。
 * @throws RobotNotFoundException
 */
function bot(): ZMRobot
{
    if (($conn = LightCacheInside::get('connect', 'conn_fd')) === -2) {
        return OneBotV11::getRandom();
    }
    if ($conn !== -1) {
        if (($obj = ManagerGM::get($conn)) !== null) {
            return new ZMRobot($obj);
        }
        throw new RobotNotFoundException('单机器人连接模式可能连接了多个机器人！');
    }
    throw new RobotNotFoundException('没有任何机器人连接到框架！');
}

/**
 * 获取指定连接类型的文件描述符ID
 */
function get_all_fd_of_type(string $type = 'default'): array
{
    $fds = [];
    foreach (ManagerGM::getAllByName($type) as $obj) {
        $fds[] = $obj->getFd();
    }
    return $fds;
}

/**
 * 获取原子计数器
 *
 * 与 {@link ZMAtomic::get()} 一致
 */
function zm_atomic(string $name): ?Atomic
{
    return ZMAtomic::get($name);
}

/**
 * 生成 UUID
 */
function uuidgen(bool $uppercase = false): string
{
    try {
        $data = random_bytes(16);
    } catch (Exception $e) {
        return '';
    }
    $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3F | 0x80);
    return $uppercase ? strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4))) :
        vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * 获取框架运行的工作目录
 *
 * @example 例如你是从 /root/framework-starter/ 目录启动的框架，vendor/bin/start server，那么 working_dir() 返回的就是 /root/framework-starter。
 */
function working_dir(): string
{
    return WORKING_DIR;
}

/**
 * 更漂亮地输出变量值
 *
 * 可替代 {@link var_dump()}
 *
 * @param  mixed       $var
 * @return array|mixed 返回传入的变量，传入多个变量则会返回数组
 */
function zm_dump($var, ...$moreVars)
{
    VarDumper::dump($var);

    foreach ($moreVars as $v) {
        VarDumper::dump($v);
    }

    if (func_num_args() > 1) {
        return func_get_args();
    }

    return $var;
}

/**
 * 输出info日志
 *
 * 与 {@link Console::info()} 一致
 *
 * @param $obj
 */
function zm_info($obj): void
{
    Console::info($obj);
}

/**
 * 输出warning日志
 *
 * 与 {@link Console::warning()} 一致
 *
 * @param $obj
 */
function zm_warning($obj): void
{
    Console::warning($obj);
}

/**
 * 输出success日志
 *
 * 与 {@link Console::success()} 一致
 *
 * @param $obj
 */
function zm_success($obj): void
{
    Console::success($obj);
}

/**
 * 输出debug日志
 *
 * 与 {@link Console::debug()} 一致
 *
 * @param $obj
 */
function zm_debug($obj): void
{
    Console::debug($obj);
}

/**
 * 输出verbose日志
 *
 * 与 {@link Console::verbose()} 一致
 *
 * @param $obj
 */
function zm_verbose($obj): void
{
    Console::verbose($obj);
}

/**
 * 输出error日志
 *
 * 与 {@link Console::error()} 一致
 *
 * @param $obj
 */
function zm_error($obj): void
{
    Console::error($obj);
}

/**
 * 获取配置项
 *
 * 与 {@link ZMConfig::get()} 一致
 *
 * @return mixed
 */
function zm_config(string $name, ?string $key = null)
{
    return ZMConfig::get($name, $key);
}

/**
 * 生成快速回复闭包
 *
 * @param $reply
 */
function quick_reply_closure($reply): Closure
{
    return static function () use ($reply) {
        return $reply;
    };
}

/**
 * 获取内部错误码
 *
 * @param $code
 */
function zm_internal_errcode($code): string
{
    return "[ErrCode:{$code}] ";
}

/**
 * 以下为废弃的函数，将于未来移除
 */

/**
 * @deprecated 已废弃，请使用 {@link get_all_fd_of_type()}
 */
function getAllFdByConnectType(string $type = 'default'): array
{
    return get_all_fd_of_type($type);
}

/**
 * @param mixed $class_name
 * @deprecated 已废弃，请使用 {@link get_class_path()}
 */
function getClassPath($class_name): ?string
{
    return get_class_path($class_name);
}

/**
 * @param mixed $msg
 * @param mixed $ban_comma
 * @deprecated 已废弃，请使用 {@link explode_msg()}，参数有变
 */
function explodeMsg($msg, $ban_comma = false): array
{
    if ($ban_comma) {
        return explode_msg($msg, [' ']);
    }

    return explode_msg($msg);
}

/**
 * @deprecated 已废弃，请使用 {@link current_connection_is()}
 */
function connectIsQQ(): bool
{
    return current_connection_is('qq');
}

/**
 * @deprecated 已废弃，请使用 {@link current_connection_is()}
 */
function connectIsDefault(): bool
{
    return current_connection_is('default');
}

/**
 * @param mixed $type
 * @deprecated 已废弃，请使用 {@link current_connection_is()}
 */
function connectIs($type): bool
{
    return current_connection_is($type);
}

/**
 * @deprecated 已废弃，请使用 {@link get_annotations()}
 */
function getAnnotations(): array
{
    return get_annotations();
}

/**
 * @param mixed $pattern
 * @param mixed $context
 * @deprecated 已废弃，请使用 {@link match_args()}
 */
function matchArgs($pattern, $context)
{
    return match_args($pattern, $context);
}

/**
 * @param mixed $pattern
 * @param mixed $context
 * @deprecated 已废弃，请使用 {@link match_pattern()}
 */
function matchPattern($pattern, $context): bool
{
    return match_pattern($pattern, $context);
}

/**
 * @param mixed $message_type
 * @deprecated 已废弃，请使用 {@link get_onebot_target_id_name()}
 */
function onebot_target_id_name(string $message_type): string
{
    return get_onebot_target_id_name($message_type);
}

/**
 * @deprecated 已废弃，请直接使用 {@link call_with_catch()}
 */
function zm_go(callable $callable)
{
    call_with_catch($callable);
}

/**
 * @param mixed $v
 * @deprecated 已废弃，请使用 {@link hash_message()}
 */
function zm_data_hash($v): string
{
    return hash_message($v);
}
