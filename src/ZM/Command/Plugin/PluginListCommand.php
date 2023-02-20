<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use ZM\Plugin\PluginManager;

#[AsCommand(name: 'plugin:list', description: '显示插件列表')]
class PluginListCommand extends PluginCommand
{
    protected function handle(): int
    {
        $all = PluginManager::getPlugins();
        $table = new Table($this->output);
        $table->setHeaders(['名称', '版本', '类型']);
        foreach ($all as $k => $v) {
            $table->addRow([$k, $v->getVersion(), $this->getTypeDisplayName($v->getPluginType())]);
        }
        $table->setStyle('box');
        $table->render();
        return static::SUCCESS;
    }
}
