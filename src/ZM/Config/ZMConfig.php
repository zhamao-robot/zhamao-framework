<?php

declare(strict_types=1);

namespace ZM\Config;

use ZM\Console\Console;
use ZM\Exception\ConfigException;
use ZM\Utils\DataProvider;

class ZMConfig
{
    public const SUPPORTED_EXTENSIONS = ['php', 'json'];

    public const SUPPORTED_ENVIRONMENTS = ['development', 'production', 'staging'];

    private const DEFAULT_PATH = __DIR__ . '/../../../config';

    /** @var string 上次报错 */
    public static $last_error = '';

    /** @var array 配置文件 */
    public static $config = [];

    /** @var string 配置文件 */
    private static $path = '.';

    /** @var string 上次的路径 */
    private static $last_path = '.';

    /** @var string 配置文件环境变量 */
    private static $env = 'development';

    /** @var array 配置文件元数据 */
    private static $config_meta_list = [];

    public static function setDirectory($path)
    {
        self::$last_path = self::$path;
        return self::$path = $path;
    }

    /**
     * @internal
     */
    public static function restoreDirectory()
    {
        self::$path = self::$last_path;
        self::$last_path = '.';
    }

    public static function getDirectory(): string
    {
        return self::$path;
    }

    public static function setEnv($env = 'development'): bool
    {
        if (!in_array($env, self::SUPPORTED_ENVIRONMENTS)) {
            throw new ConfigException('E00079', 'Unsupported environment: ' . $env);
        }
        self::$env = $env;
        return true;
    }

    public static function getEnv(): string
    {
        return self::$env;
    }

    /**
     * @param  mixed                  $additional_key
     * @throws ConfigException
     * @return null|array|false|mixed
     */
    public static function get(string $name, $additional_key = '')
    {
        $separated = explode('.', $name);
        if ($additional_key !== '') {
            $separated = array_merge($separated, explode('.', $additional_key));
        }
        $head_name = array_shift($separated);
        // 首先判断有没有初始化这个配置文件，因为是只读，所以是懒加载，加载第一次后缓存起来
        if (!isset(self::$config[$head_name])) {
            Console::debug('配置文件' . $name . ' ' . $additional_key . '没读取过，正在从文件加载 ...');
            self::$config[$head_name] = self::loadConfig($head_name);
        }
        // global.remote_terminal
        // 根据切分来寻找子配置
        $obj = self::$config[$head_name];
        foreach ($separated as $key) {
            if (isset($obj[$key])) {
                $obj = $obj[$key];
            } else {
                return null;
            }
        }
        return $obj;
    }

    public static function trace(string $name)
    {
        // TODO: 调试配置文件搜寻路径
    }

    public static function reload()
    {
        self::$config = [];
        self::$config_meta_list = [];
    }

    public static function smartPatch($data, $patch)
    {
        /* patch 样例：
        [patch]
        runtime:
            annotation_reader_ignore: ["牛逼"]
        custom: "非常酷的patch模式"

        [base]
        runtime:
            annotation_reader_ignore: []
            reload_delay_time: 800

        [result]
        runtime:
            annotation_reader_ignore: ["牛逼"]
            reload_delay_time: 800
        custom: "非常酷的patch模式"
        */
        if (is_array($data) && is_array($patch)) { // 两者必须是数组才行
            if (is_assoc_array($patch) && is_assoc_array($data)) { // 两者必须都是kv数组才能递归merge，如果是顺序数组，则直接覆盖
                foreach ($patch as $k => $v) {
                    if (!isset($data[$k])) { // 如果项目不在基类存在，则直接写入
                        $data[$k] = $v;
                    } else { // 如果base存在的话，则递归patch覆盖
                        $data[$k] = self::smartPatch($data[$k], $v);
                    }
                }
                return $data;
            }
        }
        return $patch;
    }

    /**
     * @throws ConfigException
     * @return array|int|string
     */
    private static function loadConfig(string $name)
    {
        // 首先获取此名称的所有配置文件的路径
        self::parseList($name);

        $env1_patch0 = null;
        $env1_patch1 = null;
        $env0_patch0 = null;
        $env0_patch1 = null;
        foreach (self::$config_meta_list[$name] as $v) {
            /** @var ConfigMetadata $v */
            if ($v->is_env && !$v->is_patch) {
                $env1_patch0 = $v->data;
            } elseif ($v->is_env && $v->is_patch) {
                $env1_patch1 = $v->data;
            } elseif (!$v->is_env && !$v->is_patch) {
                $env0_patch0 = $v->data;
            } else {
                $env0_patch1 = $v->data;
            }
        }
        // 优先级：无env无patch < 无env有patch < 有env无patch < 有env有patch
        // 但是无patch的版本必须有一个，否则会报错
        if ($env1_patch0 === null && $env0_patch0 === null) {
            throw new ConfigException('E00078', '未找到配置文件 ' . $name . ' !');
        }
        $data = $env1_patch0 ?? $env0_patch0;
        if (is_array($patch = $env1_patch1 ?? $env0_patch1) && is_assoc_array($patch)) {
            $data = self::smartPatch($data, $patch);
        }

        return $data;
    }

    /**
     * 通过名称将所有该名称的配置文件路径和信息读取到列表中
     * @throws ConfigException
     */
    private static function parseList(string $name): void
    {
        $list = [];
        $files = DataProvider::scanDirFiles(self::$path, true, true);
        foreach ($files as $file) {
            Console::debug('正在从目录' . self::$path . '读取配置文件 ' . $file);
            $info = pathinfo($file);
            $info['extension'] = $info['extension'] ?? '';

            // 排除子文件夹名字带点的文件
            if ($info['dirname'] !== '.' && strpos($info['dirname'], '.') !== false) {
                continue;
            }

            // 判断文件名是否为配置文件
            if (!in_array($info['extension'], self::SUPPORTED_EXTENSIONS)) {
                continue;
            }

            $ext = $info['extension'];
            $dot_separated = explode('.', $info['filename']);

            // 将配置文件加进来
            $obj = new ConfigMetadata();
            if ($dot_separated[0] === $name) { // 如果文件名与配置文件名一致
                // 首先检测该文件是否为补丁版本儿
                if (str_ends_with($info['filename'], '.patch')) {
                    $obj->is_patch = true;
                    $info['filename'] = substr($info['filename'], 0, -6);
                } else {
                    $obj->is_patch = false;
                }
                // 其次检测该文件是不是带有环境参数的版本儿
                if (str_ends_with($info['filename'], '.' . self::$env)) {
                    $obj->is_env = true;
                    $info['filename'] = substr($info['filename'], 0, -(strlen(self::$env) + 1));
                } else {
                    $obj->is_env = false;
                }
                if (mb_strpos($info['filename'], '.') !== false) {
                    Console::warning('文件名 ' . $info['filename'] . ' 不合法(含有".")，请检查文件名是否合法。');
                    continue;
                }
                $obj->path = realpath(self::$path . '/' . $info['dirname'] . '/' . $info['basename']);
                $obj->extension = $ext;
                $obj->data = self::readConfigFromFile(realpath(self::$path . '/' . $info['dirname'] . '/' . $info['basename']), $info['extension']);
                $list[] = $obj;
            }
        }
        // 如果是源码模式，config目录和default目录相同，所以不需要继续采摘default目录下的文件
        if (realpath(self::$path) !== realpath(self::DEFAULT_PATH)) {
            $files = DataProvider::scanDirFiles(self::DEFAULT_PATH, true, true);
            foreach ($files as $file) {
                $info = pathinfo($file);
                $info['extension'] = $info['extension'] ?? '';
                // 判断文件名是否为配置文件
                if (!in_array($info['extension'], self::SUPPORTED_EXTENSIONS)) {
                    continue;
                }
                if ($info['filename'] === $name) { // 如果文件名与配置文件名一致
                    $obj = new ConfigMetadata();
                    $obj->is_patch = false;
                    $obj->is_env = false;
                    $obj->path = realpath(self::DEFAULT_PATH . '/' . $info['dirname'] . '/' . $info['basename']);
                    $obj->extension = $info['extension'];
                    $obj->data = self::readConfigFromFile(realpath(self::DEFAULT_PATH . '/' . $info['dirname'] . '/' . $info['basename']), $info['extension']);
                    $list[] = $obj;
                }
            }
        }
        self::$config_meta_list[$name] = $list;
    }

    /**
     * @param  mixed           $filename
     * @param  mixed           $ext_name
     * @throws ConfigException
     */
    private static function readConfigFromFile($filename, $ext_name)
    {
        Console::debug('正加载配置文件 ' . $filename);
        switch ($ext_name) {
            case 'php':
                $r = include_once $filename;
                if ($r === true) {
                    // 已经加载过的文件，掐头直接eval读取
                    $file_content = str_replace(['<?php', 'declare(strict_types=1);'], '', file_get_contents($filename));
                    // 配置文件中可能有使用到 __DIR__ 的本地变量，在 eval 中执行会发生变化，所以需要重置下
                    $file_content = str_replace('__DIR__', '"' . dirname($filename) . '"', $file_content);
                    $r = eval($file_content);
                }
                if (is_array($r)) {
                    return $r;
                }
                throw new ConfigException('E00079', 'php配置文件include失败，请检查终端warning错误');
            case 'json':
            default:
                $r = json_decode(file_get_contents($filename), true);
                if (is_array($r)) {
                    return $r;
                }
                throw new ConfigException('E00079', 'json反序列化失败，请检查文件内容');
        }
    }
}
