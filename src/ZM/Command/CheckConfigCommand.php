<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Config\ZMConfig;

class CheckConfigCommand extends Command
{
    protected static $defaultName = 'check:config';

    private $need_update = false;

    protected function configure()
    {
        $this->setDescription('检查配置文件是否和框架当前版本有更新');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (LOAD_MODE !== 1) {
            $output->writeln('<error>仅限在Composer依赖模式中使用此命令！</error>');
            return 1;
        }
        $current_cfg = getcwd() . '/config/';
        $remote_cfg = include_once FRAMEWORK_ROOT_DIR . '/config/global.php';
        if (file_exists($current_cfg . 'global.php')) {
            $this->check($remote_cfg, 'global.php', $output);
        }
        if (file_exists($current_cfg . 'global.development.php')) {
            $this->check($remote_cfg, 'global.development.php', $output);
        }
        if (file_exists($current_cfg . 'global.staging.php')) {
            $this->check($remote_cfg, 'global.staging.php', $output);
        }
        if (file_exists($current_cfg . 'global.production.php')) {
            $this->check($remote_cfg, 'global.production.php', $output);
        }
        if ($this->need_update === true) {
            $output->writeln('<comment>有配置文件需要更新，详情见文档 `https://framework.zhamao.xin/update/config`</comment>');
        } else {
            $output->writeln('<info>配置文件暂无更新！</info>');
        }

        return 0;
    }

    /**
     * @param mixed $remote
     * @param mixed $local
     */
    private function check($remote, $local, OutputInterface $out)
    {
        $local_file = include_once WORKING_DIR . '/config/' . $local;
        if ($local_file === true) {
            $local_file = ZMConfig::get('global');
        }
        foreach ($remote as $k => $v) {
            if (!isset($local_file[$k])) {
                $out->writeln('<comment>配置文件 ' . $local . " 需要更新！(当前配置文件缺少 `{$k}` 字段配置)</comment>");
                $this->need_update = true;
            }
        }
    }
}
