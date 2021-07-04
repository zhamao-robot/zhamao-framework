<?php


namespace ZM\Module;

use Exception;
use Phar;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Exception\ModulePackException;
use ZM\Exception\ZMException;
use ZM\Store\LightCache;
use ZM\Utils\DataProvider;
use ZM\Utils\ZMUtil;

/**
 * 模块构造器
 * Class ModulePacker
 * @package ZM\Module
 * @since 2.5
 */
class ModulePacker
{
    const ZM_MODULE_PACKER_VERSION = '1.0';
    /** @var array $module */
    private $module = [];
    /** @var bool $override */
    private $override = false;
    /** @var string $output_path */
    private $output_path = '';
    /** @var string $filename */
    private $filename = '';
    /** @var Phar $phar */
    private $phar;
    /** @var null|array $module_config */
    private $module_config = null;

    /**
     * @throws ModulePackException
     */
    public function __construct(array $module) {
        if (ini_get('phar.readonly') == '1') {
            throw new ModulePackException('请先在 php.ini 中设置 `phar.readonly = Off` 后再打包模块！');
        }
        if (!isset($module['name'], $module['module-path'], $module['namespace'])) {
            throw new ModulePackException('模块打包需要至少传入name、module-path、namespace三个参数！');
        }
        $this->module = $module;
    }

    /**
     * 设置输出文件夹
     * @param $path
     */
    public function setOutputPath($path) {
        $this->output_path = $path;
    }

    /**
     * 设置是否覆盖
     * @param bool $override
     */
    public function setOverride(bool $override = true) {
        $this->override = $override;
    }

    /**
     * 获取模块名字
     * @return mixed
     */
    public function getName() {
        return $this->module['name'];
    }

    /**
     * 获取打包的文件名绝对路径
     * @return string
     */
    public function getFileName(): string {
        return $this->filename;
    }

    /**
     * 打包模块
     * @throws ZMException
     */
    public function pack() {
        $this->filename = $this->output_path . '/' . $this->module['name'];
        if (isset($this->module['version'])) {
            $this->filename .= '_' . $this->module['version'];
        }
        $this->filename .= '.phar';
        if ($this->override) {
            if (file_exists($this->filename)) {
                Console::info('Overwriting ' . $this->filename);
                unlink($this->filename);
            }
        }

        $this->phar = new Phar($this->filename);
        $this->phar->startBuffering();
        Console::info('模块输出文件：' . $this->filename);

        $this->addFiles();                  //添加文件
        $this->addLightCacheStore();        //保存light-cache-store指定的项
        $this->addModuleConfig();           //生成module-config.json
        $this->addZMDataFiles();            //添加需要保存的zm_data下的目录或文件
        $this->addEntry();                  //生成模块的入口文件module_entry.php

        $this->phar->stopBuffering();
    }

    private function addFiles() {
        $file_list = DataProvider::scanDirFiles($this->module['module-path'], true, false);
        foreach ($file_list as $v) {
            $this->phar->addFile($v, $this->getRelativePath($v));
        }
    }

    private function getRelativePath($path) {
        return str_replace(realpath(DataProvider::getSourceRootDir()) . '/', '', realpath($path));
    }

    private function generatePharAutoload(): array {
        return ZMUtil::getClassesPsr4($this->module['module-path'], $this->module['namespace'], null, true);
    }

    private function getComposerAutoloadItems(): array {
        $composer = json_decode(file_get_contents(DataProvider::getSourceRootDir() . '/composer.json'), true);
        $path = self::getRelativePath($this->module['module-path']);
        $item = [];
        foreach (($composer['autoload']['psr-4'] ?? []) as $k => $v) {
            if (strpos($path, $v) === 0) {
                $item['psr-4'][$k] = $v;
            }
        }
        foreach (($composer['autoload']['files'] ?? []) as $v) {
            if (strcmp($path, $v) === 0) {
                $item['files'][] = $v;
            }
        }
        return $item;
    }

    /**
     * @throws ZMException
     * @throws Exception
     */
    private function addLightCacheStore() {
        if (isset($this->module['light-cache-store'])) {
            $store = [];
            $r = ZMConfig::get('global', 'light_cache') ?? [
                    'size' => 512,                     //最多允许储存的条数（需要2的倍数）
                    'max_strlen' => 32768,               //单行字符串最大长度（需要2的倍数）
                    'hash_conflict_proportion' => 0.6,   //Hash冲突率（越大越好，但是需要的内存更多）
                    'persistence_path' => DataProvider::getDataFolder() . '_cache.json',
                    'auto_save_interval' => 900
                ];
            LightCache::init($r);
            foreach ($this->module['light-cache-store'] as $v) {
                $r = LightCache::get($v);
                if ($r === null) {
                    Console::warning(zm_internal_errcode("E00045") . 'LightCache 项：' . $v . ' 不存在或值为null，无法为其保存。');
                } else {
                    $store[$v] = $r;
                    Console::info('打包LightCache持久化项：' . $v);
                }
            }
            $this->phar->addFromString('light_cache_store.json', json_encode($store, 128 | 256));
        }
    }

    private function addModuleConfig() {
        $stub_values = [
            'zm-module' => true,
            'generated-id' => sha1(strval(microtime(true))),
            'module-packer-version' => self::ZM_MODULE_PACKER_VERSION,
            'module-root-path' => $this->getRelativePath($this->module['module-path']),
            'namespace' => $this->module['namespace'],
            'autoload-psr-4' => $this->generatePharAutoload(),
            'unpack' => [
                'composer-autoload-items' => $this->getComposerAutoloadItems(),
                'global-config-override' => $this->module['global-config-override'] ?? false
            ],
            'allow-hotload' => $this->module["allow-hotload"] ?? false,
            'pack-time' => time()
        ];
        $this->phar->addFromString('zmplugin.json', json_encode($stub_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->module_config = $stub_values;
    }

    private function addEntry() {
        $stub_replace = [
            'generated_id' => $this->module_config['generated-id']
        ];

        $stub_template = str_replace(
            array_map(function ($x) {
                return '__' . $x . '__';
            }, array_keys($stub_replace)),
            array_values($stub_replace),
            file_get_contents(DataProvider::getFrameworkRootDir() . '/src/ZM/script_phar_stub.php')
        );
        $this->phar->addFromString('module_entry.php', $stub_template);

        $this->phar->setStub($this->phar->createDefaultStub('module_entry.php'));
    }

    /**
     * @throws ModulePackException
     */
    private function addZMDataFiles() {
        $base_dir = realpath(DataProvider::getDataFolder());
        if (is_array($this->module["zm-data-store"] ?? null)) {
            foreach ($this->module["zm-data-store"] as $v) {
                if (is_dir($base_dir . '/' . $v)) {
                    $v = rtrim($v, '/');
                    Console::info("Adding external zm_data dir: " . $v);
                    $files = DataProvider::scanDirFiles($base_dir . '/' . $v, true, true);
                    foreach ($files as $single) {
                        $this->phar->addFile($base_dir . '/' . $v . '/' . $single, 'zm_data/' . $v . '/' . $single);
                    }
                } elseif (is_file($base_dir . '/' . $v)) {
                    Console::info("Add external zm_data file: " . $v);
                    $this->phar->addFile($base_dir . '/' . $v, 'zm_data/' . $v);
                } else {
                    throw new ModulePackException(zm_internal_errcode("E00066")."`zmdata-store` 指定的文件或目录不存在");
                }
            }
        }
    }
}