<?php


namespace ZM\Command\Daemon;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DaemonStatusCommand extends DaemonCommand
{
    protected static $defaultName = 'daemon:status';

    protected function configure() {
        $this->setDescription("查看守护进程框架的运行状态（仅限--daemon模式可用）");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        parent::execute($input, $output);
        $output->writeln("<info>框架运行中，pid：" . $this->daemon_file["pid"] . "</info>");
        $output->writeln("<comment>----- 以下是stdout内容 -----</comment>");
        $stdout = file_get_contents($this->daemon_file["stdout"]);
        $stdout = explode("\n", $stdout);
        for ($i = 10; $i > 0; --$i) {
            if (isset($stdout[count($stdout) - $i]))
                echo $stdout[count($stdout) - $i] . PHP_EOL;
        }
        return 0;
    }
}
