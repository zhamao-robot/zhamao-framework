<?php #plain
use Swoole\Coroutine as Co;
use Swoole\Atomic;
use Swoole\Coroutine;
use Swoole\WebSocket\Server;
use Symfony\Component\VarDumper\VarDumper;
use ZM\API\OneBotV11;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Context\Context;
use ZM\Event\EventManager;
use ZM\Exception\RobotNotFoundException;
use ZM\Exception\ZMException;
use ZM\Framework;
use ZM\Store\LightCacheInside;
use ZM\Store\ZMAtomic;
use ZM\Store\ZMBuf;
use ZM\Utils\DataProvider;
use Swoole\Coroutine\System;
use ZM\Context\ContextInterface;


function getClassPath($class_name)
{
    $dir = str_replace("\\", "/", $class_name);
    $dir2 = DataProvider::getSourceRootDir() . "/src/" . $dir . ".php";
    //echo "@@@".$dir2.PHP_EOL;
    $dir2 = str_replace("\\", "/", $dir2);
    if (file_exists($dir2)) return $dir2;
    else return null;
}

/**
 * 检查炸毛框架运行的环境
 * @internal
 */
function _zm_env_check()
{
    if (!extension_loaded("swoole")) die(zm_internal_errcode("E00001") . "Can not find swoole extension.\n");
    if (version_compare(SWOOLE_VERSION, "4.5.0") == -1) die(zm_internal_errcode("E00002") . "You must install swoole version >= 4.5.0 !");
    if (version_compare(PHP_VERSION, "7.2") == -1) die(zm_internal_errcode("E00003") . "PHP >= 7.2 required.");
    if (version_compare(SWOOLE_VERSION, "4.6.7") < 0 && !extension_loaded("pcntl")) {
        Console::error(zm_internal_errcode("E00004") . "Swoole 版本必须不低于 4.6.7 或 PHP 安装加载了 pcntl 扩展！");
        die();
    }
}

/**
 * 使用自己定义的万（san）能分割函数
 * @param $msg
 * @param bool $ban_comma
 * @return array
 */
function explodeMsg($msg, $ban_comma = false): array
{
    $msg = str_replace(" ", "\n", trim($msg));
    if (!$ban_comma) {
        //$msg = str_replace("，", "\n", $msg);
        $msg = str_replace("\t", "\n", $msg);
    }
    $msgs = explode("\n", $msg);
    $ls = [];
    foreach ($msgs as $v) {
        if (trim($v) == "") continue;
        $ls[] = trim($v);
    }
    return $ls;
}

/** @noinspection PhpUnused */
function unicode_decode($str)
{
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function ($matches) {
        return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");
    }, $str);
}

function matchPattern($pattern, $context): bool
{
    if (mb_substr($pattern, 0, 1) == "" && mb_substr($context, 0, 1) == "")
        return true;
    if ('*' == mb_substr($pattern, 0, 1) && "" != mb_substr($pattern, 1, 1) && "" == mb_substr($context, 0, 1))
        return false;
    if (mb_substr($pattern, 0, 1) == mb_substr($context, 0, 1))
        return matchPattern(mb_substr($pattern, 1), mb_substr($context, 1));
    if (mb_substr($pattern, 0, 1) == "*")
        return matchPattern(mb_substr($pattern, 1), $context) || matchPattern($pattern, mb_substr($context, 1));
    return false;
}

function split_explode($del, $str, $divide_en = false): array
{
    $str = explode($del, $str);
    for ($i = 0; $i < mb_strlen($str[0]); $i++) {
        if (
            is_numeric(mb_substr($str[0], $i, 1)) &&
            (
                !is_numeric(mb_substr($str[0], $i - 1, 1)) &&
                mb_substr($str[0], $i - 1, 1) != ' ' &&
                ctype_alpha(mb_substr($str[0], $i - 1, 1)) === false
            )
        ) {
            $str[0] = mb_substr($str[0], 0, $i) . " " . mb_substr($str[0], $i);
        } elseif (
            $divide_en &&
            ctype_alnum(mb_substr($str[0], $i, 1)) &&
            !ctype_alnum(mb_substr($str[0], $i - 1, 1)) &&
            mb_substr($str[0], $i - 1, 1) != ' '
        ) {
            $str[0] = mb_substr($str[0], 0, $i) . ' ' . mb_substr($str[0], $i);
        }
    }
    $str = implode($del, $str);
    //echo $str."\n";
    $ls = [];
    foreach (explode($del, $str) as $v) {
        if (trim($v) == "") continue;
        $ls[] = $v;
    }
    //var_dump($ls);
    return $ls == [] ? [""] : $ls;
}

function matchArgs($pattern, $context)
{
    $result = [];
    if (matchPattern($pattern, $context)) {
        if (mb_strpos($pattern, "*") === false) return [];
        $exp = explode("*", $pattern);
        $i = 0;
        foreach ($exp as $k => $v) {
            //echo "[MATCH$k] " . $v . PHP_EOL;
            if ($v == "" && $k == 0) continue;
            elseif ($v == "" && $k == count($exp) - 1) {
                $context = $context . "^EOL";
                $v = "^EOL";
            }
            $cur_var = "";
            //echo mb_substr($context, $i) . "|" . $v . PHP_EOL;
            $ori = $i;
            while (($a = mb_substr($context, $i, mb_strlen($v))) != $v && $a != "") {
                $cur_var .= mb_substr($context, $i, 1);
                ++$i;
            }
            if ($i != $ori || $k == 1 || $k == count($exp) - 1) {
                //echo $cur_var . PHP_EOL;
                $result[] = $cur_var;
            }
            $i += mb_strlen($v);
        }
        return $result;
    } else return false;
}

function connectIsQQ(): bool
{
    return ctx()->getConnection()->getName() == 'qq';
}

function connectIsDefault(): bool
{
    return ctx()->getConnection()->getName() == 'default';
}

function connectIs($type): bool
{
    return ctx()->getConnection()->getName() == $type;
}

function getAnnotations(): array
{
    $s = debug_backtrace()[1];
    //echo json_encode($s, 128|256);
    $list = [];
    foreach (EventManager::$events as $v) {
        foreach ($v as $vs) {
            //echo get_class($vs).": ".$vs->class." => ".$vs->method.PHP_EOL;
            if ($vs->class == $s["class"] && $vs->method == $s["function"]) {
                $list[get_class($vs)][] = $vs;
            }
        }
    }
    return $list;
}

function set_coroutine_params($array)
{
    $cid = Co::getCid();
    if ($cid == -1) die(zm_internal_errcode("E00061") . "Cannot set coroutine params at none coroutine mode.");
    if (isset(Context::$context[$cid])) Context::$context[$cid] = array_merge(Context::$context[$cid], $array);
    else Context::$context[$cid] = $array;
    foreach (Context::$context as $c => $v) {
        if (!Co::exists($c)) unset(Context::$context[$c], ZMBuf::$context_class[$c]);
    }
}

/**
 * @return ContextInterface|null
 */
function context(): ?ContextInterface
{
    return ctx();
}

/**
 * @return ContextInterface|null
 */
function ctx(): ?ContextInterface
{
    $cid = Co::getCid();
    $c_class = ZMConfig::get("global", "context_class");
    if (isset(Context::$context[$cid])) {
        return ZMBuf::$context_class[$cid] ?? (ZMBuf::$context_class[$cid] = new $c_class($cid));
    } else {
        Console::debug("未找到当前协程的上下文($cid)，正在找父进程的上下文");
        while (($pcid = Co::getPcid($cid)) !== -1) {
            $cid = $pcid;
            if (isset(Context::$context[$cid])) return ZMBuf::$context_class[$cid] ?? (ZMBuf::$context_class[$cid] = new $c_class($cid));
        }
        return null;
    }
}

function onebot_target_id_name($message_type): string
{
    return ($message_type == "group" ? "group_id" : "user_id");
}

function zm_sleep($s = 1): bool
{
    if (Coroutine::getCid() != -1) System::sleep($s);
    else usleep($s * 1000 * 1000);
    return true;
}

function zm_exec($cmd): array
{
    return System::exec($cmd);
}

function zm_cid()
{
    return Co::getCid();
}

function zm_yield()
{
    Co::yield();
}

function zm_resume(int $cid)
{
    Co::resume($cid);
}

function zm_timer_after($ms, callable $callable)
{
    Swoole\Timer::after($ms, function () use ($callable) {
        call_with_catch($callable);
    });
}

function call_with_catch($callable)
{
    try {
        $callable();
    } catch (Exception $e) {
        $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
        Console::error(zm_internal_errcode("E00033") . "Uncaught exception " . get_class($e) . ": " . $error_msg);
        Console::trace();
    } catch (Error $e) {
        $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
        Console::error(zm_internal_errcode("E00033") . "Uncaught " . get_class($e) . ": " . $error_msg);
        Console::trace();
    }
}

function zm_timer_tick($ms, callable $callable)
{
    if (zm_cid() === -1) {
        return go(function () use ($ms, $callable) {
            Console::debug("Adding extra timer tick of " . $ms . " ms");
            Swoole\Timer::tick($ms, function () use ($callable) {
                call_with_catch($callable);
            });
        });
    } else {
        return Swoole\Timer::tick($ms, function () use ($callable) {
            call_with_catch($callable);
        });
    }
}

function zm_go(callable $callable)
{
    call_with_catch($callable);
}

function zm_data_hash($v): string
{
    return md5($v["user_id"] . "^" . $v["self_id"] . "^" . $v["message_type"] . "^" . ($v[$v["message_type"] . "_id"] ?? $v["user_id"]));
}

function server(): ?Server
{
    return Framework::$server;
}

/**
 * 获取缓存当前框架pid的临时目录
 * @return string
 */
function _zm_pid_dir(): string
{
    global $_ZM_HASH;
    if (!isset($_ZM_HASH)) $_ZM_HASH = md5(DataProvider::getWorkingDir());
    return '/tmp/.zm_' . $_ZM_HASH;
}

/**
 * @return OneBotV11
 * @throws RobotNotFoundException
 * @throws ZMException
 */
function bot()
{
    if (($conn = LightCacheInside::get("connect", "conn_fd")) == -2) {
        return OneBotV11::getRandom();
    } elseif ($conn != -1) {
        if (($obj = ManagerGM::get($conn)) !== null) return new OneBotV11($obj);
        else throw new RobotNotFoundException("单机器人连接模式可能连接了多个机器人！");
    } else {
        throw new RobotNotFoundException("没有任何机器人连接到框架！");
    }
}

/**
 * 获取同类型所有连接的文件描述符 ID
 * @param string $type
 * @return array
 * @author 854854321
 */
function getAllFdByConnectType(string $type = 'default'): array
{
    $fds = [];
    foreach (ManagerGM::getAllByName($type) as $obj) {
        $fds[] = $obj->getFd();
    }
    return $fds;
}

function zm_atomic($name): ?Atomic
{
    return ZMAtomic::get($name);
}

function uuidgen($uppercase = false): string
{
    try {
        $data = random_bytes(16);
    } catch (Exception $e) {
        return "";
    }
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return $uppercase ? strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4))) :
        vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function working_dir()
{
    return WORKING_DIR;
}

function zm_dump($var, ...$moreVars)
{
    VarDumper::dump($var);

    foreach ($moreVars as $v) {
        VarDumper::dump($v);
    }

    if (1 < func_num_args()) {
        return func_get_args();
    }

    return $var;
}

function zm_info($obj)
{
    Console::info($obj);
}

function zm_warning($obj)
{
    Console::warning($obj);
}

function zm_success($obj)
{
    Console::success($obj);
}

function zm_debug($obj)
{
    Console::debug($obj);
}

function zm_verbose($obj)
{
    Console::verbose($obj);
}

function zm_error($obj)
{
    Console::error($obj);
}

function zm_config($name, $key = null)
{
    return ZMConfig::get($name, $key);
}

function quick_reply_closure($reply)
{
    return function () use ($reply) {
        return $reply;
    };
}

function zm_internal_errcode($code): string
{
    return "[ErrCode:$code] ";
}
