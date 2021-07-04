<?php


namespace ZM\Utils\Manager;


use ZM\Console\Console;
use ZM\Exception\ModulePackException;
use ZM\Exception\ZMException;
use ZM\Module\ModulePacker;
use ZM\Module\ModuleUnpacker;
use ZM\Utils\DataProvider;

/**
 * 模块管理器，负责打包解包模块
 * Class ModuleManager
 * @package ZM\Utils\Manager
 * @since 2.5
 */
class ModuleManager
{

    /**
     * 扫描src目录下的所有已经被标注的模块
     * @return array
     * @throws ZMException
     */
    public static function getConfiguredModules(): array {
        $dir = DataProvider::getSourceRootDir() . "/src/";
        $ls = DataProvider::scanDirFiles($dir, true, true);
        $modules = [];
        foreach ($ls as $v) {
            $pathinfo = pathinfo($v);
            if ($pathinfo["basename"] == "zm.json") {
                $json = json_decode(file_get_contents(realpath($dir . "/" . $v)), true);
                if ($json === null) continue;
                if (!isset($json["name"])) continue;
                if ($pathinfo["dirname"] == ".") {
                    throw new ZMException(zm_internal_errcode("E00052") . "在/src/目录下不可以直接标记为模块(zm.json)，因为命名空间不能为根空间！");
                }
                $json["module-path"] = realpath($dir . "/" . $pathinfo["dirname"]);
                $json["namespace"] = str_replace("/", "\\", $pathinfo["dirname"]);
                if (isset($modules[$json["name"]])) {
                    throw new ZMException(zm_internal_errcode("E00053") . "重名模块：" . $json["name"]);
                }
                $modules[$json["name"]] = $json;
            }
        }
        return $modules;
    }

    public static function getPackedModules(): array {
        $dir = DataProvider::getDataFolder() . "modules";
        $ls = DataProvider::scanDirFiles($dir, true, false);
        if ($ls === false) return [];
        $modules = [];
        foreach ($ls as $v) {
            $pathinfo = pathinfo($v);
            if (($pathinfo["extension"] ?? "") != "phar") continue;
            $file = "phar://" . $v;
            if (!is_file($file . "/module_entry.php") || !is_file($file . "/zmplugin.json")) continue;
            $module_config = json_decode(file_get_contents($file . "/zmplugin.json"), true);
            if ($module_config === null) continue;
            if (!is_file($file . "/" . $module_config["module-root-path"] . "/zm.json")) {
                Console::warning(zm_internal_errcode("E00054") . "模块（插件）文件 " . $pathinfo["basename"] . " 无法找到模块配置文件（zm.json）！");
                continue;
            }
            $module_file = json_decode(file_get_contents($file . "/" . $module_config["module-root-path"] . "/zm.json"), true);
            if ($module_file === null) {
                Console::warning(zm_internal_errcode("E000555") . "模块（插件）文件 " . $pathinfo["basename"] . " 无法正常读取模块配置文件（zm.json）！");
                continue;
            }
            $module_config["phar-path"] = $v;
            $module_config["name"] = $module_file["name"] ?? null;
            if ($module_config["name"] === null) continue;
            $module_config["module-config"] = $module_file;
            $modules[$module_config["name"]] = $module_config;
        }
        return $modules;
    }

    /**
     * 打包模块
     * @param $module
     * @return bool
     * @throws ZMException
     */
    public static function packModule($module): bool {
        try {
            $packer = new ModulePacker($module);
            $packer->setOutputPath(DataProvider::getDataFolder() . "output");
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
     * @param $module
     * @param array $options
     * @return array|false
     */
    public static function unpackModule($module, array $options = []) {
        try {
            $packer = new ModuleUnpacker($module);
            return $packer->unpack((bool)$options["override-light-cache"], (bool)$options["override-zm-data"], (bool)$options["override-source"]);
        } catch (ZMException $e) {
            Console::error($e->getMessage());
            return false;
        }
    }
}