<?php

declare(strict_types=1);

namespace ZM\Store;

use ZM\Utils\ZMUtil;

class FileSystem
{
    /**
     * 递归或非递归扫描目录，可返回相对目录的文件列表或绝对目录的文件列表
     *
     * @param string      $dir         目录
     * @param bool        $recursive   是否递归扫描子目录
     * @param bool|string $relative    是否返回相对目录，如果为true则返回相对目录，如果为false则返回绝对目录
     * @param bool        $include_dir 非递归模式下，是否包含目录
     * @since 2.5
     */
    public static function scanDirFiles(string $dir, bool $recursive = true, bool|string $relative = false, bool $include_dir = false): array|false
    {
        $dir = zm_dir($dir);
        // 不是目录不扫，直接 false 处理
        if (!is_dir($dir)) {
            logger()->warning(zm_internal_errcode('E00080') . '扫描目录失败，目录不存在');
            return false;
        }
        logger()->debug('扫描' . $dir);
        // 套上 zm_dir
        $scan_list = scandir($dir);
        if ($scan_list === false) {
            logger()->warning(zm_internal_errcode('E00080') . '扫描目录失败，目录无法读取: ' . $dir);
            return false;
        }
        $list = [];
        // 将 relative 置为相对目录的前缀
        if ($relative === true) {
            $relative = $dir;
        }
        // 遍历目录
        foreach ($scan_list as $v) {
            // Unix 系统排除这俩目录
            if ($v == '.' || $v == '..') {
                continue;
            }
            $sub_file = zm_dir($dir . '/' . $v);
            if (is_dir($sub_file) && $recursive) {
                # 如果是 目录 且 递推 , 则递推添加下级文件
                $list = array_merge($list, self::scanDirFiles($sub_file, $recursive, $relative));
            } elseif (is_file($sub_file) || is_dir($sub_file) && !$recursive && $include_dir) {
                # 如果是 文件 或 (是 目录 且 不递推 且 包含目录)
                if (is_string($relative) && mb_strpos($sub_file, $relative) === 0) {
                    $list[] = ltrim(mb_substr($sub_file, mb_strlen($relative)), '/\\');
                } elseif ($relative === false) {
                    $list[] = $sub_file;
                }
            }
        }
        return $list;
    }

    /**
     * 检查路径是否为相对路径（根据第一个字符是否为"/"来判断）
     *
     * @param  string $path 路径
     * @return bool   返回结果
     * @since 2.5
     */
    public static function isRelativePath(string $path): bool
    {
        // 适配 Windows 的多盘符目录形式
        if (DIRECTORY_SEPARATOR === '\\') {
            return !(strlen($path) > 2 && ctype_alpha($path[0]) && $path[1] === ':');
        }
        return strlen($path) > 0 && $path[0] !== '/';
    }

    /**
     * 创建目录（如果不存在）
     *
     * @param string $path 目录路径
     */
    public static function createDir(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('无法建立目录：%s', $path));
        }
    }

    /**
     * 在工作进程中返回可以通过reload重新加载的php文件列表
     *
     * @return string[]|string[][]
     */
    public static function getReloadableFiles(): array
    {
        $array_map = [];
        global $zm_loaded_files;
        foreach (array_diff(
            get_included_files(),
            $zm_loaded_files
        ) as $key => $x) {
            $array_map[$key] = str_replace(SOURCE_ROOT_DIR . '/', '', $x);
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
    public static function getClassesPsr4(string $dir, string $base_namespace, mixed $rule = null, bool|string $return_path_value = false): array
    {
        // 预先读取下composer的file列表
        $composer = ZMUtil::getComposerMetadata();
        $classes = [];
        // 扫描目录，使用递归模式，相对路径模式，因为下面此路径要用作转换成namespace
        $files = self::scanDirFiles($dir, true, true);
        foreach ($files as $v) {
            $pathinfo = pathinfo($v);
            if (($pathinfo['extension'] ?? '') == 'php') {
                $path = rtrim($dir, '/') . '/' . rtrim($pathinfo['dirname'], './') . '/' . $pathinfo['basename'];

                // 过滤不包含类的文件
                $tokens = \PhpToken::tokenize(file_get_contents($path));
                $found = false;
                foreach ($tokens as $token) {
                    if ($token->is(T_CLASS)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    continue;
                }

                if ($rule === null) { // 规则未设置回调时候，使用默认的识别过滤规则
                    /*if (substr(file_get_contents($dir . '/' . $v), 6, 6) == '#plain') {
                        continue;
                    }*/
                    if (file_exists($dir . '/' . $v . '.ignore')) {
                        continue;
                    }
                    if (mb_substr($pathinfo['basename'], 0, 7) == 'global_' || mb_substr($pathinfo['basename'], 0, 7) == 'script_') {
                        continue;
                    }
                    foreach (($composer['autoload']['files'] ?? []) as $fi) {
                        if (md5_file(SOURCE_ROOT_DIR . '/' . $fi) == md5_file($dir . '/' . $v)) {
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
