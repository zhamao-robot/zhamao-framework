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

class ModulePackCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'module:pack';

    protected function configure() {
        $this->addArgument("module-name", InputArgument::REQUIRED);
        $this->setDescription("Build an \".phar\" file | 将项目构建一个phar包");
        $this->setHelp("此功能将会把炸毛框架的模块打包为\".phar\"，供发布和执行。");
        $this->addOption("target", "D", InputOption::VALUE_REQUIRED, "Output Directory | 指定输出目录");
        ZMConfig::setDirectory(DataProvider::getSourceRootDir() . '/config');
        ZMConfig::setEnv($args["env"] ?? "");
        if (ZMConfig::get("global") === false) {
            die (zm_internal_errcode("E00007") . "Global config load failed: " . ZMConfig::$last_error . "\nPlease init first!\nSee: https://github.com/zhamao-robot/zhamao-framework/issues/37\n");
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
        $list = ModuleManager::getConfiguredModules();
        if (!isset($list[$input->getArgument("module-name")])) {
            $output->writeln("<error>不存在模块 ".$input->getArgument("module-name")." !</error>");
            return 1;
        }
        $result = ModuleManager::packModule($list[$input->getArgument("module-name")]);
        if ($result) Console::success("打包完成！");
        else Console::error("打包失败！");
        return 0;
    }
}