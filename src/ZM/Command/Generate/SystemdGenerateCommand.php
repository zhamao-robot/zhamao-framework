<?php

declare(strict_types=1);

namespace ZM\Command\Generate;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Config\ZMConfig;
use ZM\Utils\DataProvider;

class SystemdGenerateCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'generate:systemd';

    protected function configure()
    {
        $this->setDescription('生成框架的 systemd 配置文件');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ZMConfig::setDirectory(DataProvider::getSourceRootDir() . '/config');
        $path = $this->generate();
        $output->writeln('<info>成功生成 systemd 文件，位置：' . $path . '</info>');
        $output->writeln('<info>有关如何使用 systemd 配置文件，请访问 `https://github.com/zhamao-robot/zhamao-framework/issues/36`</info>');
        return 0;
    }

    private function generate()
    {
        $s = "[Unit]\nDescription=zhamao-framework Daemon\nAfter=rc-local.service\n\n[Service]\nType=simple";
        $s .= "\nUser=" . exec('whoami');
        $s .= "\nGroup=" . exec("groups | awk '{print $1}'");
        $s .= "\nWorkingDirectory=" . getcwd();
        global $argv;
        $s .= "\nExecStart=" . PHP_BINARY . " {$argv[0]} server";
        $s .= "\nRestart=always\n\n[Install]\nWantedBy=multi-user.target\n";
        @mkdir(getcwd() . '/resources/');
        file_put_contents(ZMConfig::get('global', 'zm_data') . 'zhamao.service', $s);
        return ZMConfig::get('global', 'zm_data') . 'zhamao.service';
    }
}
