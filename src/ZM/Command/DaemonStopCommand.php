<?php


namespace ZM\Command;

use Swoole\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Utils\DataProvider;

class DaemonStopCommand extends DaemonCommand
{
    protected static $defaultName = 'daemon:stop';

    protected function configure() {
        $this->setDescription("停止守护进程下运行的框架（仅限--daemon模式可用）");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        parent::execute($input, $output);
        Process::kill(intval($this->daemon_file["pid"]), SIGINT);
        unlink(DataProvider::getWorkingDir() . "/.daemon_pid");
        $output->writeln("<info>成功停止！</info>");
        return 0;
    }
}
