<?php


namespace ZM\Command\Server;

use Swoole\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Command\Daemon\DaemonCommand;
use ZM\Utils\DataProvider;

class ServerStopCommand extends DaemonCommand
{
    protected static $defaultName = 'server:stop';

    protected function configure() {
        $this->setDescription("停止运行的框架");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        parent::execute($input, $output);
        Process::kill(intval($this->daemon_file["pid"]), SIGTERM);
        $i = 10;
        while (file_exists(DataProvider::getWorkingDir() . "/.daemon_pid") && $i > 0) {
            sleep(1);
            --$i;
        }
        if ($i === 0) {
            $output->writeln("<error>停止失败，请检查进程pid #" . $this->daemon_file["pid"] . " 是否响应！</error>");
        } else {
            $output->writeln("<info>成功停止！</info>");
        }
        return 0;
    }
}
