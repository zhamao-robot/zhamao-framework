<?php


namespace Custom\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CustomCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'custom';

    protected function configure() {
        $this->setDescription("custom description | 自定义命令的描述字段");
        $this->addOption("failure", null, null, "以错误码为1返回结果");
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if ($input->getOption("failure")) {
            $output->writeln("<error>Hello error! I am wrong message.</error>");
            return Command::FAILURE;
        } else {
            $output->writeln("<comment>Hello world! I am successful message.</comment>");
            return Command::SUCCESS;
        }
    }
}
