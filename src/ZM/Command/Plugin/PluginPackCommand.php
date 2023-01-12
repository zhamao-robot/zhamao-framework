<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use ZM\Bootstrap;
use ZM\Command\Command;
use ZM\Exception\PluginException;
use ZM\Plugin\PluginManager;

#[AsCommand(name: 'plugin:pack', description: '打包插件到 Phar 格式')]
class PluginPackCommand extends Command
{
    protected array $bootstrappers = [
        BootStrap\RegisterLogger::class,
        Bootstrap\SetInternalTimezone::class,
        Bootstrap\LoadConfiguration::class,
    ];

    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, '要打包的插件名称');
    }

    /**
     * {@inheritDoc}
     */
    protected function handle(): int
    {
        try {
            PluginManager::packPlugin($this->input->getArgument('name'));
        } catch (PluginException $e) {
            $this->error($e->getMessage());
        }
        $this->output->writeln('打包插件到 Phar 格式');
        return 0;
    }
}
