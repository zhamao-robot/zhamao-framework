<?php

declare(strict_types=1);

namespace ZM\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Console\Console;
use ZM\Utils\Manager\ModuleManager;

class ModuleUnpackCommand extends ModuleCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'module:unpack';

    protected function configure()
    {
        parent::configure();
        $this->addOption('overwrite-light-cache', null, null, '覆盖现有的LightCache项目');
        $this->addOption('overwrite-zm-data', null, null, '覆盖现有的zm_data文件');
        $this->addOption('overwrite-source', null, null, '覆盖现有的源码文件');
        $this->addOption('ignore-depends', null, null, '解包时忽略检查依赖');
        $this->addArgument('module-name', InputArgument::REQUIRED, '模块名称');
        $this->setDescription('解包一个phar模块到src目录');
        $this->setHelp('此功能将phar格式的模块包解包到src目录下。');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $list = ModuleManager::getPackedModules();
        if (!isset($list[$input->getArgument('module-name')])) {
            $output->writeln('<error>不存在打包的模块 ' . $input->getArgument('module-name') . ' !</error>');
            return 1;
        }
        $result = ModuleManager::unpackModule($list[$input->getArgument('module-name')], $input->getOptions());
        if ($result) {
            Console::success('解压完成！');
        } else {
            Console::error('解压失败！');
        }
        return 0;
    }
}
