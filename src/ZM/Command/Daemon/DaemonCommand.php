<?php


namespace ZM\Command\Daemon;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Framework;

abstract class DaemonCommand extends Command
{
    protected $daemon_file = null;

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $file = Framework::getProcessState(ZM_PROCESS_MASTER);
        if ($file === false || posix_getsid(intval($file["pid"])) === false) {
            $output->writeln("<comment>未检测到正在运行的守护进程或框架进程！</comment>");
            Framework::removeProcessState(ZM_PROCESS_MASTER);
            die();
        }
        $this->daemon_file = $file;
        return 0;
    }
}