<?php

declare(strict_types=1);

namespace ZM\Command\Daemon;

use Swoole\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DaemonReloadCommand extends DaemonCommand
{
    protected static $defaultName = 'daemon:reload';

    protected function configure()
    {
        $this->setDescription('重载框架');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        Process::kill(intval($this->daemon_file['pid']), SIGUSR1);
        $output->writeln('<info>成功重载！</info>');
        return 0;
    }
}
