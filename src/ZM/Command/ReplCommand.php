<?php

declare(strict_types=1);

namespace ZM\Command;

use Psy\Configuration;
use Psy\Shell;
use Psy\VersionUpdater\Checker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Framework;

class ReplCommand extends Command
{
    protected static $defaultName = 'repl';

    protected static $defaultDescription = '交互式控制台';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Configuration::fromInput($input);
        $config->setUpdateCheck(Checker::NEVER);
        $config->setStartupMessage('你可以使用 "help" 来查看帮助');

        $shell = new Shell($config);
        $shell->addCommands([]); // TODO: add some great commands

        try {
            $output->writeln(sprintf('<fg=blue>Zhamao Repl on Zhamao Framework %s</>', Framework::VERSION));
            $shell->run();
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
