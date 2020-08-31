<?php


namespace ZM\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SystemdCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'systemd:generate';

    protected function execute(InputInterface $input, OutputInterface $output) {
        //TODO: 写一个生成systemd配置的功能，给2.0
        return Command::SUCCESS;
    }
}
