<?php

declare(strict_types=1);

namespace ZM\Utils;

use Exception;
use Swoole\Process;
use ZM\Console\Console;
use ZM\Framework;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Store\ZMBuf;

class ZMUtil
{
    /**
     * @param  mixed     $error_exit
     * @throws Exception
     */
    public static function stop($error_exit = false)
    {
        if (SpinLock::tryLock('_stop_signal') === false) {
            return;
        }
        Console::warning(Console::setColor('Stopping server...', 'red'));
        if (Console::getLevel() >= 4) {
            Console::trace();
        }
        ZMAtomic::get('stop_signal')->set($error_exit ? 2 : 1);
        server()->shutdown();
    }

    /**
     * @throws Exception
     */
    public static function reload()
    {
        Process::kill(server()->master_pid, SIGUSR1);
    }

    public static function getModInstance($class)
    {
        if (!isset(ZMBuf::$instance[$class])) {
            // Console::debug('Class instance $class not exist, so I created it.');
            return ZMBuf::$instance[$class] = new $class();
        }
        return ZMBuf::$instance[$class];
    }

    /**
     * 在工作进程中返回可以通过reload重新加载的php文件列表
     * @return string[]|string[][]
     */
    public static function getReloadableFiles(): array
    {
        $array_map = [];
        foreach (array_diff(
            get_included_files(),
            Framework::$loaded_files
        ) as $key => $x) {
            $array_map[$key] = str_replace(DataProvider::getSourceRootDir() . '/', '', $x);
        }
        return $array_map;
    }

    /**
     * 使用Psr-4标准获取目录下的所有类
     * @param  string      $dir               目录
     * @param  string      $base_namespace    基础命名空间
     * @param  null|mixed  $rule              规则
     * @param  bool|string $return_path_value 是否返回文件路径，返回文件路径的话传入字符串
     * @return string[]
     */
    public static function getClassesPsr4(string $dir, string $base_namespace, $rule = null, $return_path_value = false): array
    {
        // 预先读取下composer的file列表
        $composer = json_decode(file_get_contents(DataProvider::getSourceRootDir() . '/composer.json'), true);
        $classes = [];
        // 扫描目录，使用递归模式，相对路径模式，因为下面此路径要用作转换成namespace
        $files = DataProvider::scanDirFiles($dir, true, true);
        foreach ($files as $v) {
            $pathinfo = pathinfo($v);
            if (($pathinfo['extension'] ?? '') == 'php') {
                $path = rtrim($dir, '/') . '/' . rtrim($pathinfo['dirname'], './') . '/' . $pathinfo['basename'];

                // 过滤不包含类的文件
                $tokens = token_get_all(file_get_contents($path));
                $found = false;
                foreach ($tokens as $token) {
                    if (!is_array($token)) {
                        continue;
                    }
                    if ($token[0] === T_CLASS) {
                        $found = true;
                    }
                }
                if (!$found) {
                    continue;
                }

                if ($rule === null) { // 规则未设置回调时候，使用默认的识别过滤规则
                    /*if (substr(file_get_contents($dir . '/' . $v), 6, 6) == '#plain') {
                        continue;
                    }*/
                    if (file_exists($dir . '/' . $v . '.plain')) {
                        continue;
                    }
                    if (mb_substr($pathinfo['basename'], 0, 7) == 'global_' || mb_substr($pathinfo['basename'], 0, 7) == 'script_') {
                        continue;
                    }
                    foreach (($composer['autoload']['files'] ?? []) as $fi) {
                        if (md5_file(DataProvider::getSourceRootDir() . '/' . $fi) == md5_file($dir . '/' . $v)) {
                            continue 2;
                        }
                    }
                } elseif (is_callable($rule) && !$rule($dir, $pathinfo)) {
                    continue;
                }
                $dirname = $pathinfo['dirname'] == '.' ? '' : (str_replace('/', '\\', $pathinfo['dirname']) . '\\');
                $class_name = $base_namespace . '\\' . $dirname . $pathinfo['filename'];
                if (is_string($return_path_value)) {
                    $classes[$class_name] = $return_path_value . '/' . $v;
                } else {
                    $classes[] = $class_name;
                }
            }
        }
        return $classes;
    }
}
