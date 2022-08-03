<?php

declare(strict_types=1);
/** @noinspection PhpUnused */

namespace ZM\Utils;

use Iterator;
use JsonSerializable;
use RuntimeException;
use Traversable;
use ZM\Config\ZMConfig;
use ZM\Console\Console;

class DataProvider
{
    public static $buffer_list = [];

    /**
     * 返回资源目录
     */
    public static function getResourceFolder(): string
    {
        return self::getWorkingDir() . '/resources/';
    }

    /**
     * 返回工作目录，不带最右边文件夹的斜杠（/）
     *
     * @return false|string
     */
    public static function getWorkingDir()
    {
        return WORKING_DIR;
    }

    /**
     * 获取框架所在根目录
     *
     * @return false|string
     */
    public static function getFrameworkRootDir()
    {
        return FRAMEWORK_ROOT_DIR;
    }

    /**
     * 获取源码根目录，除Phar模式外均与工作目录相同
     *
     * @return false|string
     */
    public static function getSourceRootDir()
    {
        return defined('SOURCE_ROOT_DIR') ? SOURCE_ROOT_DIR : WORKING_DIR;
    }

    /**
     * 获取框架反代链接
     *
     * @return null|array|false|mixed
     */
    public static function getFrameworkLink()
    {
        return ZMConfig::get('global', 'http_reverse_link');
    }

    /**
     * 获取zm_data数据目录，如果二级目录不为空，则自动创建目录并返回
     *
     * @return null|array|false|mixed|string
     */
    public static function getDataFolder(string $second = '')
    {
        if ($second !== '') {
            if (!is_dir(ZM_DATA . $second)) {
                @mkdir(ZM_DATA . $second);
            }
            if (!is_dir(ZM_DATA . $second)) {
                return false;
            }
            return realpath(ZM_DATA . $second) . '/';
        }
        return ZM_DATA;
    }

    /**
     * 将变量保存在zm_data下的数据目录，传入数组
     *
     * @param  string                                                 $filename   文件名
     * @param  array|int|Iterator|JsonSerializable|string|Traversable $file_array 文件内容数组
     * @return false|int                                              返回文件大小或false
     */
    public static function saveToJson(string $filename, $file_array)
    {
        $path = ZMConfig::get('global', 'config_dir');
        $r = explode('/', $filename);
        if (count($r) == 2) {
            $path = $path . $r[0] . '/';
            if (!is_dir($path)) {
                mkdir($path);
            }
            $name = $r[1];
        } elseif (count($r) != 1) {
            Console::warning(zm_internal_errcode('E00057') . '存储失败，文件名只能有一级目录');
            return false;
        } else {
            $name = $r[0];
        }
        return file_put_contents($path . $name . '.json', json_encode($file_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 从json加载变量到内存
     *
     * @param  string     $filename 文件名
     * @return null|mixed 返回文件内容数据或null
     */
    public static function loadFromJson(string $filename)
    {
        $path = ZMConfig::get('global', 'config_dir');
        if (file_exists($path . $filename . '.json')) {
            return json_decode(file_get_contents($path . $filename . '.json'), true);
        }
        return null;
    }

    /**
     * 递归或非递归扫描目录，可返回相对目录的文件列表或绝对目录的文件列表
     *
     * @param  string            $dir         目录
     * @param  bool              $recursive   是否递归扫描子目录
     * @param  bool|mixed|string $relative    是否返回相对目录，如果为true则返回相对目录，如果为false则返回绝对目录
     * @param  bool              $include_dir 非递归模式下，是否包含目录
     * @return array|false
     * @since 2.5
     */
    public static function scanDirFiles(string $dir, bool $recursive = true, $relative = false, bool $include_dir = false)
    {
        $dir = rtrim($dir, '/');
        if (!is_dir($dir)) {
            return false;
        }
        $r = scandir($dir);
        if ($r === false) {
            return false;
        }
        $list = [];
        if ($relative === true) {
            $relative = $dir;
        } elseif ($relative !== false && !is_string($relative)) {
            Console::warning(zm_internal_errcode('E00058') . "Relative path is not generated: wrong base directory ({$relative})");
            return false;
        }
        foreach ($r as $v) {
            if ($v == '.' || $v == '..') {
                continue;
            }
            $sub_file = $dir . '/' . $v;
            if (is_dir($sub_file) && $recursive) {
                # 如果是 目录 且 递推 , 则递推添加下级文件
                $list = array_merge($list, self::scanDirFiles($sub_file, $recursive, $relative));
            } elseif (is_file($sub_file) || is_dir($sub_file) && !$recursive && $include_dir) {
                # 如果是 文件 或 (是 目录 且 不递推 且 包含目录)
                if (is_string($relative) && mb_strpos($sub_file, $relative) === 0) {
                    $list[] = ltrim(mb_substr($sub_file, mb_strlen($relative)), '/');
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
        return strlen($path) > 0 && $path[0] !== '/';
    }

    /**
     * 创建目录（如果不存在）
     *
     * @param string $path 目录路径
     */
    public static function createIfNotExists(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('无法建立目录：%s', $path));
        }
    }
}
