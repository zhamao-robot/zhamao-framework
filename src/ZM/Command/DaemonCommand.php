<?php


namespace ZM\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Utils\DataProvider;

abstract class DaemonCommand extends Command
{
    protected $daemon_file = null;

    protected function execute(InputInterface $input, OutputInterface $output) {
        $pid_path = DataProvider::getWorkingDir() . "/.daemon_pid";
        if (!file_exists($pid_path)) {
            $output->writeln("<comment>没有检测到正在运行的守护进程！</comment>");
            die();
        }
        $file = json_decode(file_get_contents($pid_path), true);
        if ($file === null || posix_getsid(intval($file["pid"])) === false) {
            $output->writeln("<comment>未检测到正在运行的守护进程！</comment>");
            unlink($pid_path);
            die();
        }
        $this->daemon_file = $file;
        return Command::SUCCESS;
    }
}