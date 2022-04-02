<?php

declare(strict_types=1);

namespace ZM\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Console\Console;
use ZM\Exception\ZMException;
use ZM\Utils\DataProvider;
use ZM\Utils\Manager\ModuleManager;

class ModulePackCommand extends ModuleCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'module:pack';

    protected function configure()
    {
        parent::configure();
        $this->addArgument('module-name', InputArgument::REQUIRED);
        $this->setDescription('将配置好的模块构建一个phar包');
        $this->setHelp('此功能将会把炸毛框架的模块打包为".phar"，供发布和执行。');
        $this->addOption('target', 'D', InputOption::VALUE_REQUIRED, 'Output Directory | 指定输出目录');
    }

    /**
     * @throws ZMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $list = ModuleManager::getConfiguredModules();
        if (!isset($list[$input->getArgument('module-name')])) {
            $output->writeln('<error>不存在模块 ' . $input->getArgument('module-name') . ' !</error>');
            return 1;
        }
        $result = ModuleManager::packModule($list[$input->getArgument('module-name')], $input->getOption('target') ?? (DataProvider::getDataFolder() . '/output'));
        if ($result) {
            Console::success('打包完成！');
        } else {
            Console::error('打包失败！');
        }
        return 0;
    }
}
