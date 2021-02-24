<?php #plain

use Swoole\Coroutine;
use ZM\API\ZMRobot;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Context\Context;
use ZM\Event\EventManager;
use ZM\Exception\RobotNotFoundException;
use ZM\Exception\ZMException;
use ZM\Framework;
use ZM\Store\LightCacheInside;
use ZM\Store\ZMBuf;
use ZM\Utils\DataProvider;
use Swoole\Coroutine\System;
use ZM\Context\ContextInterface;


function phar_classloader($p) {
    $filepath = getClassPath($p);
    if ($filepath === null) {
        Console::debug("F:Warning: get class path wrongs.$p");
        return;
    }
    try {
        /** @noinspection PhpIncludeInspection */
        require_once $filepath;
    } catch (Exception $e) {
        echo "Error when finding class: " . $p . PHP_EOL;
        die;
    }
}

function getClassPath($class_name) {
    $dir = str_replace("\\", "/", $class_name);
    $dir2 = WORKING_DIR . "/src/" . $dir . ".php";
    //echo "@@@".$dir2.PHP_EOL;
    $dir2 = str_replace("\\", "/", $dir2);
    if (file_exists($dir2)) return $dir2;
    else {
        $dir = DataProvider::getWorkingDir() . "/src/" . $dir . ".php";
        //echo "###".$dir.PHP_EOL;
        if (file_exists($dir)) return $dir;
        else return null;
    }
}

/**
 * 使用自己定义的万（san）能分割函数
 * @param $msg
 * @param bool $ban_comma
 * @return array
 */
function explodeMsg($msg, $ban_comma = false) {
    $msg = str_replace(" ", "\n", trim($msg));
    if (!$ban_comma) {
        //$msg = str_replace("，", "\n", $msg);
        $msg = str_replace("\t", "\n", $msg);
    }
    $msgs = explode("\n", $msg);
    $ls = [];
    foreach ($msgs as $k => $v) {
        if (trim($v) == "") continue;
        $ls[] = trim($v);
    }
    return $ls;
}

function unicode_decode($str) {
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function ($matches) {
        return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");
    }, $str);
}

/**
 * 获取模块文件夹下的每个类文件的类名称
 * @param $dir
 * @param $indoor_name
 * @return array
 */
function getAllClasses($dir, $indoor_name) {
    if (!is_dir($dir)) return [];
    $list = scandir($dir);
    $classes = [];
    if ($list[0] == '.') unset($list[0], $list[1]);
    foreach ($list as $v) {
        //echo "Finding " . $dir . $v . PHP_EOL;
        //echo "At " . $indoor_name . PHP_EOL;
        if (is_dir($dir . $v)) $classes = array_merge($classes, getAllClasses($dir . $v . "/", $indoor_name . "\\" . $v));
        elseif (mb_substr($v, -4) == ".php") {
            if (substr(file_get_contents($dir . $v), 6, 6) == "#plain") continue;
            $composer = json_decode(file_get_contents(DataProvider::getWorkingDir() . "/composer.json"), true);
            foreach ($composer["autoload"]["files"] as $fi) {
                if (realpath(DataProvider::getWorkingDir() . "/" . $fi) == realpath($dir . $v)) {
                    continue 2;
                }
            }
            if ($v == "global_function.php") continue;
            $class_name = $indoor_name . "\\" . mb_substr($v, 0, -4);
            $classes [] = $class_name;
        }
    }
    return $classes;
}

function matchPattern($pattern, $context) {
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

function split_explode($del, $str, $divide_en = false) {
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
    foreach (explode($del, $str) as $k => $v) {
        if (trim($v) == "") continue;
        $ls[] = $v;
    }
    //var_dump($ls);
    return $ls == [] ? [""] : $ls;
}

function matchArgs($pattern, $context) {
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

function connectIsQQ() {
    return ctx()->getConnection()->getName() == 'qq';
}

function connectIsDefault() {
    return ctx()->getConnection()->getName() == 'default';
}

function connectIs($type) {
    return ctx()->getConnection()->getName() == $type;
}

function getAnnotations() {
    $s = debug_backtrace()[1];
    //echo json_encode($s, 128|256);
    $list = [];
    foreach (EventManager::$events as $event => $v) {
        foreach ($v as $ks => $vs) {
            //echo get_class($vs).": ".$vs->class." => ".$vs->method.PHP_EOL;
            if ($vs->class == $s["class"] && $vs->method == $s["function"]) {
                $list[get_class($vs)][] = $vs;
            }
        }
    }
    return $list;
}

function set_coroutine_params($array) {
    $cid = Co::getCid();
    if ($cid == -1) die("Cannot set coroutine params at none coroutine mode.");
    if (isset(Context::$context[$cid])) Context::$context[$cid] = array_merge(Context::$context[$cid], $array);
    else Context::$context[$cid] = $array;
    foreach (Context::$context as $c => $v) {
        if (!Co::exists($c)) unset(Context::$context[$c], ZMBuf::$context_class[$c]);
    }
}

/**
 * @return ContextInterface|null
 */
function context() {
    return ctx();
}

/**
 * @return ContextInterface|null
 */
function ctx() {
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

function zm_debug($msg) { Console::debug($msg); }

function onebot_target_id_name($message_type) {
    return ($message_type == "group" ? "group_id" : "user_id");
}

function zm_sleep($s = 1) {
    if (Coroutine::getCid() != -1) System::sleep($s);
    else usleep($s * 1000 * 1000);
    return true;
}

function zm_exec($cmd): array { return System::exec($cmd); }

function zm_cid() { return Co::getCid(); }

function zm_yield() { Co::yield(); }

function zm_resume(int $cid) { Co::resume($cid); }

function zm_timer_after($ms, callable $callable) {
    go(function () use ($ms, $callable) {
        Swoole\Timer::after($ms, $callable);
    });
}

function zm_timer_tick($ms, callable $callable) {
    if (zm_cid() === -1) {
        return go(function () use ($ms, $callable) {
            Console::debug("Adding extra timer tick of " . $ms . " ms");
            Swoole\Timer::tick($ms, $callable);
        });
    } else {
        return Swoole\Timer::tick($ms, $callable);
    }
}

function zm_data_hash($v) {
    return md5($v["user_id"] . "^" . $v["self_id"] . "^" . $v["message_type"] . "^" . ($v[$v["message_type"] . "_id"] ?? $v["user_id"]));
}

function server() {
    return Framework::$server;
}

/**
 * @return ZMRobot
 * @throws RobotNotFoundException
 * @throws ZMException
 */
function bot() {
    if (($conn = LightCacheInside::get("connect", "conn_fd")) == -2) {
        return ZMRobot::getRandom();
    } elseif ($conn != -1) {
        if (($obj = ManagerGM::get($conn)) !== null) return new ZMRobot($obj);
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
function getAllFdByConnectType(string $type = 'default'): array {
    $fds = [];
    foreach (ManagerGM::getAllByName($type) as $obj) {
        $fds[] = $obj->getFd();
    }
    return $fds;
}

function zm_atomic($name) {
    return \ZM\Store\ZMAtomic::get($name);
}

function uuidgen($uppercase = false) {
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

function working_dir() {
    if (LOAD_MODE == 0) return WORKING_DIR;
    elseif (LOAD_MODE == 1) return LOAD_MODE_COMPOSER_PATH;
    elseif (LOAD_MODE == 2) return realpath('.');
    return null;
}
