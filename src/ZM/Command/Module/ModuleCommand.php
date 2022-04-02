<?php

declare(strict_types=1);

namespace ZM\Command\Module;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Utils\DataProvider;

abstract class ModuleCommand extends Command
{
    protected function configure()
    {
        $this->addOption('env', null, InputOption::VALUE_REQUIRED, '设置环境类型 (production, development, staging)', '');
        $this->addOption('log-theme', null, InputOption::VALUE_REQUIRED, '改变终端的主题配色', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ZMConfig::setDirectory(DataProvider::getSourceRootDir() . '/config');
        ZMConfig::setEnv($input->getOption('env'));
        if (ZMConfig::get('global') === false) {
            exit(zm_internal_errcode('E00007') . 'Global config load failed: ' . ZMConfig::$last_error . "\nPlease init first!\nSee: https://github.com/zhamao-robot/zhamao-framework/issues/37\n");
        }

        // 定义常量
        /** @noinspection PhpIncludeInspection */
        include_once DataProvider::getFrameworkRootDir() . '/src/ZM/global_defines.php';

        Console::init(
            ZMConfig::get('global', 'info_level') ?? 2,
            null,
            $input->getOption('log-theme'),
            ($o = ZMConfig::get('console_color')) === false ? [] : $o
        );

        $timezone = ZMConfig::get('global', 'timezone') ?? 'Asia/Shanghai';
        date_default_timezone_set($timezone);
        return 0;
    }
}
