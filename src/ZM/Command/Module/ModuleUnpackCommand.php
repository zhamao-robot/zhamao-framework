<?php


namespace ZM\Command\Module;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->setDefinition([
            new InputArgument("module-name", InputArgument::REQUIRED),
            new InputOption("overwrite-light-cache", null, null, "覆盖现有的LightCache项目"),
            new InputOption("overwrite-zm-data", null, null, "覆盖现有的zm_data文件"),
            new InputOption("overwrite-source", null, null, "覆盖现有的源码文件"),
            new InputOption("ignore-depends", null, null, "解包时忽略检查依赖")
        ]);
        $this->setDescription("解包一个phar模块到src目录");
        $this->setHelp("此功能将phar格式的模块包解包到src目录下。");
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        ZMConfig::setDirectory(DataProvider::getSourceRootDir() . '/config');
        ZMConfig::setEnv($args["env"] ?? "");
        if (ZMConfig::get("global") === false) {
            die (zm_internal_errcode("E00007") . "Global config load failed: " . ZMConfig::$last_error . "\nPlease init first!\nSee: https://github.com/zhamao-robot/zhamao-framework/issues/37\n");
        }

        //定义常量
        /** @noinspection PhpIncludeInspection */
        include_once DataProvider::getFrameworkRootDir()."/src/ZM/global_defines.php";

        Console::init(
            ZMConfig::get("global", "info_level") ?? 4,
            null,
            $args["log-theme"] ?? "default",
            ($o = ZMConfig::get("console_color")) === false ? [] : $o
        );

        $timezone = ZMConfig::get("global", "timezone") ?? "Asia/Shanghai";
        date_default_timezone_set($timezone);

        $list = ModuleManager::getPackedModules();
        if (!isset($list[$input->getArgument("module-name")])) {
            $output->writeln("<error>不存在打包的模块 ".$input->getArgument("module-name")." !</error>");
            return 1;
        }
        $result = ModuleManager::unpackModule($list[$input->getArgument("module-name")], $input->getOptions());
        if ($result) Console::success("解压完成！");
        else Console::error("解压失败！");
        return 0;
    }
}