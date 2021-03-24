<?php


namespace ZM\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckConfigCommand extends Command
{
    protected static $defaultName = 'check-config';

    private $need_update = false;

    protected function configure() {
        $this->setDescription("检查配置文件是否和框架当前版本有更新");
    }

    /** @noinspection PhpIncludeInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        if (LOAD_MODE !== 1) {
            $output->writeln("<error>仅限在Composer依赖模式中使用此命令！");
            return Command::FAILURE;
        }
        $current_cfg = WORKING_DIR . "/config/";
        $remote_cfg = include_once $current_cfg . "/vendor/zhamao/framework/config/global.php";
        if (file_exists($current_cfg . "global.php")) {
            $this->check($remote_cfg, "global.php", $output);
        }
        if (file_exists($current_cfg . "global.development.php")) {
            $this->check($remote_cfg, "global.development.php", $output);
        }
        if (file_exists($current_cfg . "global.staging.php")) {
            $this->check($remote_cfg, "global.staging.php", $output);
        }
        if (file_exists($current_cfg . "global.production.php")) {
            $this->check($remote_cfg, "global.production.php", $output);
        }
        if ($this->need_update === true) {
            $output->writeln("<comment>有配置文件需要更新，详情见文档 https://framework.zhamao.xin/update/config.md</comment>");
        }
        return Command::SUCCESS;
    }

    private function check($remote, $local, OutputInterface $out) {
        $local_file = include_once WORKING_DIR . "/config/".$local;
        foreach($remote as $k => $v) {
            if (!isset($local_file[$k])) {
                $out->writeln("<info>配置文件 ".$local . " 需要更新！</info>");
                $this->need_update = true;
                return;
            }
        }
    }
}
