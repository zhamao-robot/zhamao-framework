<?php

declare(strict_types=1);

namespace ZM\Utils\Manager;

use Iterator;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Exception\ModulePackException;
use ZM\Exception\ZMException;
use ZM\Exception\ZMKnownException;
use ZM\Module\ModulePacker;
use ZM\Module\ModuleUnpacker;
use ZM\Utils\DataProvider;

/**
 * 模块管理器，负责打包解包模块
 * Class ModuleManager
 * @since 2.5
 */
class ModuleManager
{
    public static function getComposer()
    {
        return json_decode(file_get_contents(DataProvider::getSourceRootDir() . '/composer.json'), true);
    }

    /**
     * 扫描src目录下的所有已经被标注的模块
     * @throws ZMException
     */
    public static function getConfiguredModules(): array
    {
        $composer = self::getComposer();
        $dir = DataProvider::getSourceRootDir() . '/src/';
        $ls = DataProvider::scanDirFiles($dir, true, true);
        $modules = [];
        foreach ($ls as $v) {
            $pathinfo = pathinfo($v);
            if ($pathinfo['basename'] == 'zm.json') {
                $json = json_decode(file_get_contents(realpath($dir . '/' . $v)), true);
                if ($json === null) {
                    continue;
                }
                if (!isset($json['name'])) {
                    continue;
                }
                if ($pathinfo['dirname'] == '.') {
                    throw new ZMKnownException('E00052', '在/src/目录下不可以直接标记为模块(zm.json)，因为命名空间不能为根空间！');
                }
                $json['module-path'] = realpath($dir . '/' . $pathinfo['dirname']);

                $relative_path = str_replace(DataProvider::getSourceRootDir() . '/', '', $json['module-path']);
                foreach (array_merge($composer['autoload']['psr-4'] ?? [], $composer['autoload-dev']['psr-4'] ?? []) as $ks => $vs) {
                    if (strpos($relative_path, $vs) === 0) {
                        $remain = trim(substr($relative_path, strlen($vs)), '/');
                        $remain = str_replace('/', '\\', $remain);
                        $json['namespace'] = $ks . $remain;
                        break;
                    }
                }
                // $json['namespace'] = str_replace('/', '\\', $pathinfo['dirname']);

                if (isset($modules[$json['name']])) {
                    throw new ZMKnownException('E00053', '重名模块：' . $json['name']);
                }
                $modules[$json['name']] = $json;
            }
        }
        return $modules;
    }

    public static function getPackedModules(): array
    {
        $dir = ZMConfig::get('global', 'module_loader')['load_path'] ?? (ZM_DATA . 'modules');
        $ls = DataProvider::scanDirFiles($dir, true, false);
        if ($ls === false) {
            return [];
        }
        $modules = [];
        foreach ($ls as $v) {
            $pathinfo = pathinfo($v);
            if (($pathinfo['extension'] ?? '') != 'phar') {
                continue;
            }
            $file = 'phar://' . $v;
            if (!is_file($file . '/module_entry.php') || !is_file($file . '/zmplugin.json')) {
                continue;
            }
            $module_config = json_decode(file_get_contents($file . '/zmplugin.json'), true);
            if ($module_config === null) {
                continue;
            }
            if (!is_file($file . '/' . $module_config['module-root-path'] . '/zm.json')) {
                logger()->warning(zm_internal_errcode('E00054') . '模块（插件）文件 ' . $pathinfo['basename'] . ' 无法找到模块配置文件（zm.json）！');
                continue;
            }
            $module_file = json_decode(file_get_contents($file . '/' . $module_config['module-root-path'] . '/zm.json'), true);
            if ($module_file === null) {
                logger()->warning(zm_internal_errcode('E000555') . '模块（插件）文件 ' . $pathinfo['basename'] . ' 无法正常读取模块配置文件（zm.json）！');
                continue;
            }
            $module_config['phar-path'] = $v;
            $module_config['name'] = $module_file['name'] ?? null;
            if ($module_config['name'] === null) {
                continue;
            }
            $module_config['module-config'] = $module_file;
            $modules[$module_config['name']] = $module_config;
        }
        return $modules;
    }

    public static function getComposerModules()
    {
        $vendor_file = DataProvider::getSourceRootDir() . '/vendor/composer/installed.json';
        $obj = json_decode(file_get_contents($vendor_file), true);
        if ($obj === null) {
            return [];
        }
        $modules = [];
        foreach ($obj['packages'] as $v) {
            if (isset($v['extra']['zm']['module-path'])) {
                if (is_array($v['extra']['zm']['module-path'])) {
                    foreach ($v['extra']['zm']['module-path'] as $module_path) {
                        $m = self::getComposerModuleInfo($v, $module_path);
                        if ($m !== null) {
                            $modules[$m['name']] = $m;
                        }
                    }
                } elseif (is_string($v['extra']['zm']['module-path'])) {
                    $m = self::getComposerModuleInfo($v, $v['extra']['zm']['module-path']);
                    if ($m !== null) {
                        $modules[$m['name']] = $m;
                    }
                }
            }
        }
        return $modules;
    }

    /**
     * 打包模块
     * @param  array       $module 模块信息
     * @param  string      $target 目标路径
     * @throws ZMException
     */
    public static function packModule(array $module, string $target): bool
    {
        try {
            $packer = new ModulePacker($module);
            if (!is_dir(DataProvider::getDataFolder())) {
                throw new ModulePackException(zm_internal_errcode('E00070') . 'zm_data dir not found!');
            }
            $path = realpath($target);
            if ($path === false) {
                mkdir($path = $target, 0755, true);
            }
            $packer->setOutputPath($path);
            $packer->setOverride();
            $packer->pack();
            return true;
        } catch (ModulePackException $e) {
            Console::error($e->getMessage());
            return false;
        }
    }

    /**
     * 解包模块
     * @param  array|Iterator $module 模块信息
     * @return array|false    返回解包的信息或false
     */
    public static function unpackModule($module, array $options = [])
    {
        try {
            $packer = new ModuleUnpacker($module);
            return $packer->unpack((bool) $options['overwrite-light-cache'], (bool) $options['overwrite-zm-data'], (bool) $options['overwrite-source'], (bool) $options['ignore-depends']);
        } catch (ZMException $e) {
            Console::error($e->getMessage());
            return false;
        }
    }

    private static function getComposerModuleInfo($v, $module_path)
    {
        $module_root_path = realpath(DataProvider::getSourceRootDir() . '/vendor/composer/' . $v['install-path'] . '/' . $module_path);
        if ($module_root_path === false) {
            logger()->warning(zm_internal_errcode('E00055') . '无法找到Composer发布的插件配置路径在包 `' . $v['name'] . '` 中！');
            return null;
        }
        $json = json_decode(file_get_contents($module_root_path . '/zm.json'), true);
        if ($json === null) {
            logger()->warning(zm_internal_errcode('E00054') . 'Composer包内无法正常读取 ' . $v['name'] . ' 的内的配置文件（zm.json）！');
            return null;
        }
        if (!isset($json['name'])) {
            return null;
        }
        $json['composer-name'] = $v['name'];
        $json['module-root-path'] = realpath(DataProvider::getSourceRootDir() . '/vendor/composer/' . $v['install-path']);
        $json['module-path'] = realpath($json['module-root-path'] . '/' . $module_path);
        if (isset($v['autoload']['psr-4'])) {
            foreach ($v['autoload']['psr-4'] as $ks => $vs) {
                $vs = trim($vs, '/');
                if (strpos($module_path, $vs) === 0) {
                    $json['namespace'] = trim($ks . str_replace('/', '\\', trim(substr($module_path, strlen($vs)), '/')), '\\');
                    break;
                }
            }
        }
        if (!isset($json['namespace'])) {
            logger()->warning(zm_internal_errcode('E00055') . '无法获取Composer发布的模块命名空间！');
            return null;
        }
        return $json;
    }
}
