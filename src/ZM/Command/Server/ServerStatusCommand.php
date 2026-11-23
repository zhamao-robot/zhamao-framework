<?php

declare(strict_types=1);

namespace ZM\Command\Server;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'server:status', description: '查看服务器状态')]
class ServerStatusCommand extends ServerCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $daemon = (bool) ($this->daemon_file['daemon'] ?? false);
        $output->writeln('<info>框架' . ($daemon ? '以守护进程模式' : '') . '运行中，pid：' . ($this->daemon_file['pid'] ?? '-') . '</info>');
        if ($daemon) {
            // stdout 可能为 null（历史版本的守护进程没有记录日志路径），这里要兜住，
            // 否则 file_get_contents(null) 在 PHP 8 会直接抛 TypeError。
            $stdout_path = (string) ($this->daemon_file['stdout'] ?? '');
            if ($stdout_path === '' || !is_file($stdout_path)) {
                $output->writeln('<comment>未记录守护进程日志文件，无法显示 stdout（请查看 zm_data/logs/daemon.log）</comment>');
                return 0;
            }
            $output->writeln('<comment>----- 以下是stdout内容 -----</comment>');
            $stdout = (string) file_get_contents($stdout_path);
            $stdout = explode("\n", $stdout);
            for ($i = 15; $i > 0; --$i) {
                if (isset($stdout[count($stdout) - $i])) {
                    echo $stdout[count($stdout) - $i] . PHP_EOL;
                }
            }
        }
        return 0;
    }
}
