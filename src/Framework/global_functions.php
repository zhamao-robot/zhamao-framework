<?php

use Framework\Console;
use Framework\ZMBuf;
use ZM\Context\ContextInterface;

function classLoader($p) {
    $filepath = getClassPath($p);
    if ($filepath === null)
        echo "F:Warning: get class path wrongs.$p\n";
    //else echo "F:DBG: Found " . $p . "\n";
    try {
        require_once $filepath;
    } catch (Exception $e) {
        echo "Error when finding class: " . $p . PHP_EOL;
        die;
    }
}

function getClassPath($class_name) {
    $dir = str_replace("\\", "/", $class_name);
    $dir = WORKING_DIR . "/src/" . $dir . ".php";
    //echo "@@@".$dir.PHP_EOL;
    $dir = str_replace("\\", "/", $dir);
    if (file_exists($dir)) return $dir;
    else return null;
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
 * @param string $indoor_name
 * @return array
 */
function getAllClasses($dir, $indoor_name) {
    $list = scandir($dir);
    $classes = [];
    unset($list[0], $list[1]);
    foreach ($list as $v) {
        //echo "Finding " . $dir . $v . PHP_EOL;
        //echo "At " . $indoor_name . PHP_EOL;
        if (is_dir($dir . $v)) $classes = array_merge($classes, getAllClasses($dir . $v . "/", $indoor_name . "\\" . $v));
        elseif (mb_substr($v, -4) == ".php") {
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

function set_coroutine_params($array) {
    $cid = Co::getCid();
    if ($cid == -1) die("Cannot set coroutine params at none coroutine mode.");
    ZMBuf::$context[$cid] = $array;
    foreach (ZMBuf::$context as $c => $v) {
        if (!Co::exists($c)) unset(ZMBuf::$context[$c]);
    }
}

/**
 * @return ContextInterface|null
 */
function context() {
    $cid = Co::getCid();
    $c_class = ZMBuf::globals("context_class");
    if (isset(ZMBuf::$context[$cid])) {
        return new $c_class(ZMBuf::$context[$cid], $cid);
    } else {
        while (($pcid = Co::getPcid($cid)) !== -1) {
            if (isset(ZMBuf::$context[$cid])) return new $c_class(ZMBuf::$context[$cid], $cid);
            else $cid = $pcid;
        }
        return null;
    }
}

function debug($msg) {
    if (ZMBuf::$atomics["info_level"]->get() == 1)
        Console::log(date("[H:i:s ") . "DEBUG] " . $msg, 'gray');
}
