<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Bootstrap;
use ZM\Command\Command;

abstract class PluginCommand extends Command
{
    protected array $bootstrappers = [
        BootStrap\RegisterLogger::class,
        Bootstrap\SetInternalTimezone::class,
        Bootstrap\LoadConfiguration::class,
        Bootstrap\LoadPlugins::class,
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return parent::execute($input, $output);
    }
}
