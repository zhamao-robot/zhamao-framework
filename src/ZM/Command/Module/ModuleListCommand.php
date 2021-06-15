<?php


namespace ZM\Command\Module;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Utils\DataProvider;
use ZM\Utils\Manager\ModuleManager;

class ModuleListCommand extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'module:list';

    protected function configure() {
        $this->setDescription("Build an \".phar\" file | 将项目构建一个phar包");
        $this->setHelp("此功能将会把炸毛框架的模块打包为\".phar\"，供发布和执行。");
        ZMConfig::setDirectory(DataProvider::getSourceRootDir() . '/config');
        ZMConfig::setEnv($args["env"] ?? "");
        if (ZMConfig::get("global") === false) {
            die ("Global config load failed: " . ZMConfig::$last_error . "\nPlease init first!\n");
        }

        //定义常量
        include_once DataProvider::getFrameworkRootDir() . "/src/ZM/global_defines.php";

        Console::init(
            ZMConfig::get("global", "info_level") ?? 2,
            null,
            $args["log-theme"] ?? "default",
            ($o = ZMConfig::get("console_color")) === false ? [] : $o
        );

        $timezone = ZMConfig::get("global", "timezone") ?? "Asia/Shanghai";
        date_default_timezone_set($timezone);
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $list = ModuleManager::getConfiguredModules();

        foreach ($list as $v) {
            echo "[" . Console::setColor($v["name"], "green") . "]" . PHP_EOL;
            $out_list = ["类型" => "source"];
            if (isset($v["version"])) $out_list["版本"] = $v["version"];
            if (isset($v["description"])) $out_list["描述"] = $v["description"];
            $out_list["目录"] = str_replace(DataProvider::getSourceRootDir() . "/", "", $v["module-path"]);
            $this->printList($out_list);
        }
        $list = ModuleManager::getPackedModules();
        foreach ($list as $v) {
            echo "[" . Console::setColor($v["name"], "gold") . "]" . PHP_EOL;
            $out_list = ["类型" => "archive(phar)"];
            if (isset($v["module-config"]["version"])) $out_list["版本"] = $v["module-config"]["version"];
            if (isset($v["module-config"]["description"])) $out_list["描述"] = $v["module-config"]["description"];
            $out_list["位置"] = str_replace(DataProvider::getSourceRootDir() . "/", "", $v["phar-path"]);
            $this->printList($out_list);
        }
        if ($list === []) {
            echo Console::setColor("没有发现已编写打包配置文件（zm.json）的模块和已打包且装载的模块！", "yellow") . PHP_EOL;
        }
        return 0;
    }

    private function printList($list) {
        foreach ($list as $k => $v) {
            echo "\t" . $k . ": " . Console::setColor($v, "yellow") . PHP_EOL;
        }
    }
}