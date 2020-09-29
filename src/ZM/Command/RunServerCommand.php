<?php


namespace ZM\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Framework;

class RunServerCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'server';

    protected function configure() {
        $this->setDescription("Run zhamao-framework | 启动框架");
        $this->setHelp("直接运行可以启动");
        $this->addOption("debug-mode", "D", null, "开启调试模式 (这将关闭协程化)");
        $this->addOption("log-debug", null, null, "调整消息等级到debug (log-level=4)");
        $this->addOption("log-verbose", null, null, "调整消息等级到verbose (log-level=3)");
        $this->addOption("log-info", null, null, "调整消息等级到info (log-level=2)");
        $this->addOption("log-warning", null, null, "调整消息等级到warning (log-level=1)");
        $this->addOption("log-error", null, null, "调整消息等级到error (log-level=0)");
        $this->addOption("log-theme", null, InputOption::VALUE_REQUIRED, "改变终端的主题配色");
        $this->addOption("disable-console-input", null, null, "禁止终端输入内容 (后台服务时需要)");
        $this->addOption("disable-coroutine", null, null, "关闭协程Hook");
        $this->addOption("watch", null, null, "监听 src/ 目录的文件变化并热更新");
        $this->addOption("env", null, InputOption::VALUE_REQUIRED, "设置环境类型 (production, development, staging)");
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if(($opt = $input->getOption("env")) !== null) {
            if(!in_array($opt, ["production", "staging", "development"])) {
                $output->writeln("<error> \"--env\" option only accept production, development and staging ! </error>");
                return Command::FAILURE;
            }
        }
        // ... put here the code to run in your command
        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable
        new Framework($input->getOptions());
        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;
    }
}
