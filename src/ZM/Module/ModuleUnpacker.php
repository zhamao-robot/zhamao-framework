<?php


namespace ZM\Module;


use Jelix\Version\VersionComparator;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Exception\ModulePackException;
use ZM\Exception\ZMException;
use ZM\Store\LightCache;
use ZM\Utils\DataProvider;
use ZM\Utils\Manager\ModuleManager;

class ModuleUnpacker
{
    private $module;

    private $module_config = null;

    private $light_cache = null;

    private $unpack_data_files = [];

    public function __construct(array $module) {
        $this->module = $module;
    }

    /**
     * 解包模块
     * @param bool $override_light_cache
     * @param bool $override_data_files
     * @param bool $override_source
     * @param bool $ignore_depends
     * @return array
     * @throws ModulePackException
     * @throws ZMException
     */
    public function unpack(bool $override_light_cache = false, bool $override_data_files = false, bool $override_source = false, $ignore_depends = false): array {
        $this->checkConfig();
        $this->checkDepends($ignore_depends);
        $this->checkLightCacheStore();
        $this->checkZMDataStore();

        $this->mergeComposer();
        $this->copyZMDataStore($override_data_files);
        $this->copyLightCacheStore($override_light_cache);

        $this->mergeGlobalConfig();
        $this->copySource($override_source);
        return $this->module;
    }

    /**
     * 检查模块配置文件是否正确地放在phar包的位置中
     * @return void
     */
    private function checkConfig() {
        $config = "phar://" . $this->module["phar-path"] . "/" . $this->module["module-root-path"] . "/zm.json";
        $this->module_config = json_decode(file_get_contents($config), true);
    }

    /**
     * 检查模块依赖关系
     * @param bool $ignore_depends
     * @throws ModulePackException
     * @throws ZMException
     */
    private function checkDepends($ignore_depends = false) {
        $configured = ModuleManager::getConfiguredModules();
        $depends = $this->module_config["depends"] ?? [];
        foreach ($depends as $k => $v) {
            if (!isset($configured[$k]) && !$ignore_depends) {
                throw new ModulePackException(zm_internal_errcode("E00064") . "模块 " . $this->module_config["name"] . " 依赖的模块 $k 不存在");
            }
            $current_ver = $configured[$k]["version"] ?? "1.0";
            if (!VersionComparator::compareVersionRange($current_ver, $v) && !$ignore_depends) {
                throw new ModulePackException(zm_internal_errcode("E00063") . "模块 " . $this->module_config["name"] . " 依赖的模块 $k 版本依赖不符合条件（现有版本: " . $current_ver . ", 需求版本: " . $v . "）");
            }
        }
    }

    /**
     * 检查 light-cache-store 项是否合规
     * @throws ModulePackException
     */
    private function checkLightCacheStore() {
        if (isset($this->module_config["light-cache-store"])) {
            $file = json_decode(file_get_contents("phar://" . $this->module["phar-path"] . "/light_cache_store.json"), true);
            if ($file === null) throw new ModulePackException(zm_internal_errcode("E00065") . "模块系统检测到打包的模块文件中未含有 `light_cache_store.json` 文件");
            $this->light_cache = $file;
        }
    }

    /**
     * @throws ModulePackException
     */
    private function checkZMDataStore() {
        if (is_array($this->module_config["zm-data-store"] ?? null)) {
            foreach ($this->module_config["zm-data-store"] as $v) {
                if (!file_exists("phar://" . $this->module["phar-path"] . "/zm_data/" . $v)) {
                    throw new ModulePackException(zm_internal_errcode("E00067") . "压缩包损坏，内部找不到待解压的 zm_data 原始数据");
                }
                $file = "phar://" . $this->module["phar-path"] . "/zm_data/" . $v;
                if (is_dir($file)) {
                    $all = DataProvider::scanDirFiles($file, true, true);
                    foreach ($all as $single) {
                        $this->unpack_data_files[$file . "/" . $single] = DataProvider::getDataFolder() . $v . "/" . $single;
                    }
                } else {
                    $this->unpack_data_files[$file] = DataProvider::getDataFolder() . $v;
                }
            }
        }
    }

    private function mergeComposer() {
        $composer_file = DataProvider::getWorkingDir() . "/composer.json";
        if (!file_exists($composer_file)) throw new ModulePackException(zm_internal_errcode("E00068"));
        $composer = json_decode(file_get_contents($composer_file), true);
        if (isset($this->module_config["composer-extend-autoload"])) {
            $autoload = $this->module_config["composer-extend-autoload"];
            if (isset($autoload["psr-4"])) {
                Console::info("Adding extended autoload psr-4 for composer");
                $composer["autoload"]["psr-4"] = isset($composer["autoload"]["psr-4"]) ? array_merge($composer["autoload"]["psr-4"], $autoload["psr-4"]) : $autoload["psr-4"];
            }
            if (isset($autoload["files"])) {
                Console::info("Adding extended autoload file for composer");
                $composer["autoload"]["files"] = isset($composer["autoload"]["files"]) ? array_merge($composer["autoload"]["files"], $autoload["files"]) : $autoload["files"];
            }
        }
        if (isset($this->module_config["composer-extend-require"])) {
            foreach ($this->module_config["composer-extend-require"] as $k => $v) {
                Console::info("Adding extended required composer library: " . $k);
                if (!isset($composer[$k])) $composer[$k] = $v;
            }
        }
        file_put_contents($composer_file, json_encode($composer, 64 | 128 | 256));
    }

    /**
     * @throws ModulePackException
     */
    private function copyZMDataStore($override_data) {
        foreach ($this->unpack_data_files as $k => $v) {
            $pathinfo = pathinfo($v);
            if (!is_dir($pathinfo["dirname"])) @mkdir($pathinfo["dirname"], 0755, true);
            if (is_file($v) && $override_data !== true) {
                Console::info("Skipping zm_data file (not overwriting): " . $v);
                continue;
            }
            Console::info("Copying zm_data file: " . $v);
            if (copy($k, $v) !== true) {
                throw new ModulePackException(zm_internal_errcode("E00068") . "Cannot copy file: " . $v);
            }
        }
    }

    private function copyLightCacheStore($override) {
        $r = ZMConfig::get('global', 'light_cache') ?? [
                'size' => 512,                     //最多允许储存的条数（需要2的倍数）
                'max_strlen' => 32768,               //单行字符串最大长度（需要2的倍数）
                'hash_conflict_proportion' => 0.6,   //Hash冲突率（越大越好，但是需要的内存更多）
                'persistence_path' => DataProvider::getDataFolder() . '_cache.json',
                'auto_save_interval' => 900
            ];
        LightCache::init($r);
        foreach (($this->light_cache ?? []) as $k => $v) {
            if (LightCache::isset($k) && $override !== true) continue;
            LightCache::addPersistence($k);
            LightCache::set($k, $v);
        }
    }

    private function mergeGlobalConfig() {
        if ($this->module["unpack"]["global-config-override"] !== false) {
            $prompt = !is_string($this->module["unpack"]["global-config-override"]) ? "请根据模块提供者提供的要求进行修改 global.php 中对应的配置项" : $this->module["unpack"]["global-config-override"];
            Console::warning("模块作者要求用户手动修改 global.php 配置文件中的项目：");
            Console::warning("*" . $prompt);
            echo Console::setColor("请输入修改模式，y(使用vim修改)/e(自行使用其他编辑器修改后确认)/N(默认暂不修改)：[y/e/N] ", "gold");
            $r = strtolower(trim(fgets(STDIN)));
            switch ($r) {
                case "y":
                    system("vim " . escapeshellarg(DataProvider::getWorkingDir() . "/config/global.php") . " > `tty`");
                    Console::info("已使用 vim 修改！");
                    break;
                case "e":
                    echo Console::setColor("请修改后文件点击回车即可继续 [Enter] ", "gold");
                    fgets(STDIN);
                    break;
                case "n":
                    Console::info("暂不修改 global.php");
                    break;
            }
        }
    }

    private function copySource(bool $override_source) {
        $origin_base = "phar://" . $this->module["phar-path"] . "/" . $this->module["module-root-path"];
        $dir = DataProvider::scanDirFiles($origin_base, true, true);
        $base = DataProvider::getSourceRootDir() . "/" . $this->module["module-root-path"];
        foreach ($dir as $v) {
            $info = pathinfo($base . "/" . $v);
            if (!is_dir($info["dirname"])) {
                @mkdir($info["dirname"], 0755, true);
            }
            if (is_file($base . "/" . $v) && $override_source !== true) {
                Console::info("Skipping source file (not overwriting): " . $v);
                continue;
            }
            Console::info("Releasing source file: " . $this->module["module-root-path"] . "/" . $v);

            if (copy($origin_base . "/" . $v, $base . "/" . $v) !== true) {
                throw new ModulePackException(zm_internal_errcode("E00068") . "Cannot copy file: " . $v);
            }
        }
    }
}