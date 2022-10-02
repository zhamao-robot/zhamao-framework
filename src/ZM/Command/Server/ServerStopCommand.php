<?php

declare(strict_types=1);

namespace ZM\Command\Server;

use Swoole\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Process\ProcessStateManager;
use ZM\Store\FileSystem;

class ServerStopCommand extends ServerCommand
{
    protected static $defaultName = 'server:stop';

    protected function configure()
    {
        $this->setDescription('停止运行的框架');
        $this->setDefinition([
            new InputOption('force', 'f', InputOption::VALUE_NONE, '强制停止'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('force') !== false) {
            $file_path = ZM_STATE_DIR;
            $list = FileSystem::scanDirFiles($file_path, false, true);
            foreach ($list as $file) {
                $name = explode('.', $file);
                if (end($name) == 'pid') {
                    $pid = file_get_contents($file_path . '/' . $file);
                    Process::kill((int) $pid, SIGKILL);
                } elseif ($file === 'master.json') {
                    $json = json_decode(file_get_contents($file_path . '/' . $file), true);
                    Process::kill($json['pid'], SIGKILL);
                }
                unlink($file_path . '/' . $file);
            }
        } else {
            parent::execute($input, $output);
        }
        if ($this->daemon_file !== null) {
            Process::kill(intval($this->daemon_file['pid']), SIGTERM);
        }
        $i = 10;
        while (ProcessStateManager::getProcessState(ZM_PROCESS_MASTER) !== false && $i > 0) {
            sleep(1);
            --$i;
        }
        if ($i === 0) {
            $output->writeln('<error>停止失败，请检查进程pid #' . $this->daemon_file['pid'] . ' 是否响应！</error>');
            $output->writeln('<error>或者可以尝试使用参数 --force 来强行杀死所有进程</error>');
        } else {
            $output->writeln('<info>成功停止！</info>');
        }
        return 0;
    }
}
