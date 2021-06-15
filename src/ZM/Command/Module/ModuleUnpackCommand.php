<?php


namespace ZM\Command\Module;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Utils\DataProvider;
use ZM\Utils\Manager\ModuleManager;

class ModuleUnpackCommand extends Command
{
// the name of the command (the part after "bin/console")
    protected static $defaultName = 'module:unpack';

    protected function configure() {
        $this->addArgument("module-name", InputArgument::REQUIRED);
        $this->setDescription("Unpack a phar module into src directory");
        $this->setHelp("此功能将phar格式的模块包解包到src目录下。");
        ZMConfig::setDirectory(DataProvider::getSourceRootDir() . '/config');
        ZMConfig::setEnv($args["env"] ?? "");
        if (ZMConfig::get("global") === false) {
            die ("Global config load failed: " . ZMConfig::$last_error . "\nPlease init first!\n");
        }

        //定义常量
        include_once DataProvider::getFrameworkRootDir()."/src/ZM/global_defines.php";

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
        $list = ModuleManager::getPackedModules();
        if (!isset($list[$input->getArgument("module-name")])) {
            $output->writeln("<error>不存在打包的模块 ".$input->getArgument("module-name")." !</error>");
            return 1;
        }
        $result = ModuleManager::unpackModule($list[$input->getArgument("module-name")]);
        if ($result) Console::success("解压完成！");
        else Console::error("解压失败！");
        return 0;
    }
}