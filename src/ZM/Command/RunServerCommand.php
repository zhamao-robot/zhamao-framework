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
        $this->setDefinition([
            new InputOption("debug-mode", "D", null, "开启调试模式 (这将关闭协程化)"),
            new InputOption("log-debug", null, null, "调整消息等级到debug (log-level=4)"),
            new InputOption("log-verbose", null, null, "调整消息等级到verbose (log-level=3)"),
            new InputOption("log-info", null, null, "调整消息等级到info (log-level=2)"),
            new InputOption("log-warning", null, null, "调整消息等级到warning (log-level=1)"),
            new InputOption("log-error", null, null, "调整消息等级到error (log-level=0)"),
            new InputOption("log-theme", null, InputOption::VALUE_REQUIRED, "改变终端的主题配色"),
            new InputOption("disable-console-input", null, null, "禁止终端输入内容 (后台服务时需要)"),
            new InputOption("disable-coroutine", null, null, "关闭协程Hook"),
            new InputOption("daemon", null, null, "以守护进程的方式运行框架"),
            new InputOption("watch", null, null, "监听 src/ 目录的文件变化并热更新"),
            new InputOption("env", null, InputOption::VALUE_REQUIRED, "设置环境类型 (production, development, staging)"),
        ]);
        $this->setDescription("Run zhamao-framework | 启动框架");
        $this->setHelp("直接运行可以启动");

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
        (new Framework($input->getOptions()))->start();
        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;
    }
}
