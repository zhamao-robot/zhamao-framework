<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use ZM\Exception\PluginException;
use ZM\Plugin\PluginManager;

#[AsCommand(name: 'plugin:pack', description: '打包插件到 Phar 格式')]
class PluginPackCommand extends PluginCommand
{
    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, '要打包的插件名称');
        $this->addOption('build-dir', 'D', InputOption::VALUE_REQUIRED, '指定输出文件位置', WORKING_DIR . '/build');

        // 下面是辅助用的，和 server:start 一样
        $this->addOption('config-dir', null, InputOption::VALUE_REQUIRED, '指定其他配置文件目录');
    }

    /**
     * {@inheritDoc}
     */
    protected function handle(): int
    {
        try {
            $outpupt = PluginManager::packPlugin(
                plugin_name: $this->input->getArgument('name'),
                build_dir: $this->input->getOption('build-dir'),
                command_context: $this
            );
            $this->info("插件打包完成，输出文件：{$outpupt}");
        } catch (PluginException $e) {
            $this->error($e->getMessage());
        }
        $this->output->writeln('打包插件到 Phar 格式');
        return 0;
    }
}
