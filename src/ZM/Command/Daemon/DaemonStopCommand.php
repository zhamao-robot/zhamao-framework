<?php

declare(strict_types=1);

namespace ZM\Command\Daemon;

use Swoole\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Framework;

class DaemonStopCommand extends DaemonCommand
{
    protected static $defaultName = 'daemon:stop';

    protected function configure()
    {
        $this->setDescription('停止运行的框架');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        Process::kill(intval($this->daemon_file['pid']), SIGTERM);
        $i = 10;
        while (Framework::getProcessState(ZM_PROCESS_MASTER) !== false && $i > 0) {
            sleep(1);
            --$i;
        }
        if ($i === 0) {
            $output->writeln('<error>停止失败，请检查进程pid #' . $this->daemon_file['pid'] . ' 是否响应！</error>');
        } else {
            $output->writeln('<info>成功停止！</info>');
        }
        return 0;
    }
}
