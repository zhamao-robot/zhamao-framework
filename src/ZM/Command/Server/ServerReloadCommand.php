<?php

declare(strict_types=1);

namespace ZM\Command\Server;

use Swoole\Process;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'server:reload', description: '重载服务器')]
class ServerReloadCommand extends ServerCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        Process::kill(intval($this->daemon_file['pid']), SIGUSR1);
        $output->writeln('<info>成功重载！</info>');
        return 0;
    }
}
