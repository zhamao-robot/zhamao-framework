<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use ZM\Plugin\PluginManager;

#[AsCommand(name: 'plugin:list', description: '显示插件列表')]
class PluginListCommand extends PluginCommand
{
    protected function configure()
    {
        $this->addOption('name-list', 'N', null, '只输出插件列表的名字');
    }

    protected function handle(): int
    {
        $all = PluginManager::getPlugins();
        if ($all === []) {
            $this->info('当前未安装任何插件');
            return static::SUCCESS;
        }
        if ($this->input->getOption('name-list')) {
            $this->info('插件列表: ');
            foreach ($all as $k => $v) {
                $this->write($k);
            }
            return static::SUCCESS;
        }
        $table = new Table($this->output);
        $table->setColumnMaxWidth(2, 27);
        $table->setHeaders(['名称', '版本', '简介', '类型']);
        foreach ($all as $k => $v) {
            $table->addRow([$k, $v->getVersion(), $v->getDescription(), $this->getTypeDisplayName($v->getPluginType())]);
        }
        $table->setStyle('box');
        $table->render();
        return static::SUCCESS;
    }
}
