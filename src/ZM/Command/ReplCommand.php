<?php

declare(strict_types=1);

namespace ZM\Command;

use Psy\Configuration;
use Psy\Shell;
use Psy\VersionUpdater\Checker;
use Symfony\Component\Console\Attribute\AsCommand;
use ZM\Framework;

#[AsCommand(name: 'repl', description: '交互式控制台')]
class ReplCommand extends Command
{
    public function handle(): int
    {
        $config = Configuration::fromInput($this->input);
        $config->setUpdateCheck(Checker::NEVER);
        $config->setStartupMessage('你可以使用 "help" 来查看帮助');

        $shell = new Shell($config);
        $shell->addCommands([]); // TODO: add some great commands

        try {
            $this->info(sprintf('<fg=blue>Zhamao Repl on Zhamao Framework %s</>', Framework::VERSION));
            $shell->run();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
