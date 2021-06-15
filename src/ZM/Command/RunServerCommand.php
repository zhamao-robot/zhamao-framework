<?php


namespace ZM\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Framework;

class RunServerCommand extends Command
{
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
            new InputOption("disable-console-input", null, null, "禁止终端输入内容 (废弃)"),
            new InputOption("interact", null, null, "打开终端输入"),
            new InputOption("remote-terminal", null, null, "启用远程终端，配置使用global.php中的"),
            new InputOption("disable-coroutine", null, null, "关闭协程Hook"),
            new InputOption("daemon", null, null, "以守护进程的方式运行框架"),
            new InputOption("worker-num", null, InputOption::VALUE_REQUIRED, "启动框架时运行的 Worker 进程数量"),
            new InputOption("task-worker-num", null, InputOption::VALUE_REQUIRED, "启动框架时运行的 TaskWorker 进程数量"),
            new InputOption("watch", null, null, "监听 src/ 目录的文件变化并热更新"),
            new InputOption("show-php-ver", null, null, "启动时显示PHP和Swoole版本"),
            new InputOption("env", null, InputOption::VALUE_REQUIRED, "设置环境类型 (production, development, staging)"),
            new InputOption("disable-safe-exit", null, null, "关闭安全退出（关闭后按CtrlC时直接杀死进程）"),
            new InputOption("preview", null, null, "只显示参数，不启动服务器"),
            new InputOption("force-load-module", null, InputOption::VALUE_OPTIONAL, "强制打包状态下加载模块（使用英文逗号分割多个）")
        ]);
        $this->setDescription("Run zhamao-framework | 启动框架");
        $this->setHelp("直接运行可以启动");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        if (($opt = $input->getOption("env")) !== null) {
            if (!in_array($opt, ["production", "staging", "development", ""])) {
                $output->writeln("<error> \"--env\" option only accept production, development, staging and [empty] ! </error>");
                return 1;
            }
        }
        (new Framework($input->getOptions()))->start();
        return 0;
    }
}
