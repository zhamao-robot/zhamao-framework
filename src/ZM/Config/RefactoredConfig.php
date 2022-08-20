<?php

declare(strict_types=1);

namespace ZM\Config;

use Onebot\V12\Config\Config;
use ZM\Exception\ConfigException;

class RefactoredConfig
{
    /**
     * @var array 支持的文件扩展名
     */
    public const ALLOWED_FILE_EXTENSIONS = ['php', 'yaml', 'yml', 'json', 'toml'];

    /**
     * @var array 配置文件加载顺序，后覆盖前
     */
    public const LOAD_ORDER = ['global', 'environment', 'patch'];

    /**
     * @var array 已加载的配置文件
     */
    private $loaded_files = [];

    /**
     * @var array 配置文件路径
     */
    private $config_paths;

    /**
     * @var string 当前环境
     */
    private $environment;

    /**
     * @var Config 内部配置容器
     */
    private $holder;

    /**
     * 构造配置实例
     *
     * @param array  $config_paths 配置文件路径
     * @param string $environment  环境
     *
     * @throws ConfigException 配置文件加载出错
     */
    public function __construct(array $config_paths, string $environment = 'development')
    {
        $this->config_paths = $config_paths;
        $this->environment = $environment;
        $this->holder = new Config([]);
        $this->loadFiles();
    }

    /**
     * 加载配置文件
     *
     * @throws ConfigException
     */
    public function loadFiles(): void
    {
        $stages = [
            'global' => [],
            'environment' => [],
            'patch' => [],
        ];

        // 遍历所有需加载的文件，并按加载类型进行分组
        foreach ($this->config_paths as $config_path) {
            $files = scandir($config_path);
            foreach ($files as $file) {
                // 略过不支持的文件
                if (!in_array(pathinfo($file, PATHINFO_EXTENSION), self::ALLOWED_FILE_EXTENSIONS, true)) {
                    continue;
                }

                $file_path = $config_path . '/' . $file;
                if (is_dir($file_path)) {
                    // TODO: 支持子目录（待定）
                    continue;
                }

                $file = pathinfo($file, PATHINFO_FILENAME);

                // 略过不应加载的文件
                if (!$this->shouldLoadFile($file)) {
                    continue;
                }

                // 略过加载顺序未知的文件
                $load_type = $this->getFileLoadType($file);
                if (!in_array($load_type, self::LOAD_ORDER, true)) {
                    continue;
                }

                // 将文件加入到对应的加载阶段
                $stages[$load_type][] = $file_path;
            }
        }

        // 按照加载顺序加载配置文件
        foreach (self::LOAD_ORDER as $load_type) {
            foreach ($stages[$load_type] as $file_path) {
                $this->loadConfigFromPath($file_path);
            }
        }
    }

    /**
     * 合并传入的配置数组至指定的配置项
     *
     * @param string $key    目标配置项，必须为数组
     * @param array  $config 要合并的配置数组
     */
    public function merge(string $key, array $config): void
    {
        $original = $this->get($key, []);
        $this->set($key, array_merge($original, $config));
    }

    /**
     * 获取配置项
     *
     * @param string $key     配置项名称，可使用.访问数组
     * @param mixed  $default 默认值
     *
     * @return null|array|mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->holder->get($key, $default);
    }

    /**
     * 设置配置项
     * 仅在本次运行期间生效，不会保存到配置文件中哦
     *
     * @param string $key   配置项名称，可使用.访问数组
     * @param mixed  $value 要写入的值，传入 null 会进行删除
     */
    public function set(string $key, $value): void
    {
        $this->holder->set($key, $value);
    }

    /**
     * 获取内部配置容器
     */
    public function getHolder(): Config
    {
        return $this->holder;
    }

    /**
     * 重载配置文件
     * 运行期间新增的配置文件不会被加载哟~
     *
     * @throws ConfigException
     */
    public function reload(): void
    {
        $this->holder = new Config([]);
        $this->loadFiles();
    }

    /**
     * 获取文件加载类型
     *
     * @param string $name 文件名
     *
     * @return string 可能为：global, environment, patch
     */
    private function getFileLoadType(string $name): string
    {
        // 传入此处的 name 参数有三种可能的格式：
        // 1. 纯文件名：如 test，此时加载类型为 global
        // 2. 文件名.环境：如 test.development，此时加载类型为 environment
        // 3. 文件名.patch：如 test.patch，此时加载类型为 patch
        // 至于其他的格式，则为未定义行为
        if (strpos($name, '.') === false) {
            return 'global';
        }
        $name_and_env = explode('.', $name);
        if (count($name_and_env) !== 2) {
            return 'undefined';
        }
        if ($name_and_env[1] === 'patch') {
            return 'patch';
        }
        return 'environment';
    }

    /**
     * 判断是否应该加载配置文件
     *
     * @param string $name 文件名
     */
    private function shouldLoadFile(string $name): bool
    {
        // 传入此处的 name 参数有两种可能的格式：
        // 1. 纯文件名：如 test
        // 2. 文件名.环境：如 test.development
        // 对于第一种格式，在任何情况下均应该加载
        // 对于第二种格式，只有当环境与当前环境相同时才加载
        // 至于其他的格式，则为未定义行为
        if (strpos($name, '.') === false) {
            return true;
        }
        $name_and_env = explode('.', $name);
        if (count($name_and_env) !== 2) {
            return false;
        }
        return $name_and_env[1] === $this->environment;
    }

    /**
     * 从传入的路径加载配置文件
     *
     * @param string $path 配置文件路径
     *
     * @throws ConfigException 传入的配置文件不支持
     */
    private function loadConfigFromPath(string $path): void
    {
        if (in_array($path, $this->loaded_files, true)) {
            return;
        }
        $this->loaded_files[] = $path;

        // 判断文件格式是否支持
        $info = pathinfo($path);
        $name = $info['filename'];
        $ext = $info['extension'];
        if (!in_array($ext, self::ALLOWED_FILE_EXTENSIONS, true)) {
            throw new ConfigException('E00079', "不支持的配置文件格式：{$ext}");
        }

        // 读取并解析配置
        $content = file_get_contents($path);
        $config = [];
        switch ($ext) {
            case 'php':
                $config = require $path;
                break;
            case 'json':
                $config = json_decode($content, true);
                break;
            case 'yaml':
            case 'yml':
                // TODO: 实现yaml解析
                break;
            case 'toml':
                // TODO: 实现toml解析
                break;
            default:
                throw new ConfigException('E00079', "不支持的配置文件格式：{$ext}");
        }

        // 加入配置
        $this->merge($name, $config);
    }
}
