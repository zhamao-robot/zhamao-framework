<?php

declare(strict_types=1);

namespace ZM\Command\Server;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Exception\ZMKnownException;
use ZM\Process\ProcessStateManager;

abstract class ServerCommand extends Command
{
    protected $daemon_file;

    /**
     * @throws ZMKnownException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = ProcessStateManager::getProcessState(ZM_PROCESS_MASTER);
        /* @noinspection PhpComposerExtensionStubsInspection */
        if ($file === false || posix_getsid(intval($file['pid'])) === false) {
            $output->writeln('<comment>未检测到正在运行的守护进程或框架进程！</comment>');
            if (ProcessStateManager::isStateEmpty()) {
                ProcessStateManager::removeProcessState(ZM_PROCESS_MASTER);
            } else {
                $output->writeln('<comment>检测到可能残留的守护进程或框架进程，请使用命令关闭：server:stop --force</comment>');
            }
            exit(1);
        }
        $this->daemon_file = $file;
        return 0;
    }
}
