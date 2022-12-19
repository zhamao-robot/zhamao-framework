<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'check:config', description: '检查配置文件是否和框架当前版本有更新')]
class CheckConfigCommand extends Command
{
    use SourceLoadModeOnly;

    private bool $need_update = false;

    protected function handle(): int
    {
        $current_cfg = SOURCE_ROOT_DIR . '/config/';
        $remote_cfg = include FRAMEWORK_ROOT_DIR . '/config/global_old.php';
        if (file_exists($current_cfg . 'global.php')) {
            $this->check($remote_cfg, 'global.php');
        }
        if (file_exists($current_cfg . 'global.development.php')) {
            $this->check($remote_cfg, 'global.development.php');
        }
        if (file_exists($current_cfg . 'global.staging.php')) {
            $this->check($remote_cfg, 'global.staging.php');
        }
        if (file_exists($current_cfg . 'global.production.php')) {
            $this->check($remote_cfg, 'global.production.php');
        }
        if ($this->need_update === true) {
            $this->comment('有配置文件需要更新，详情见文档 `https://framework.zhamao.xin/update/config`');
        } else {
            $this->info('配置文件暂无更新！');
        }

        return self::SUCCESS;
    }

    private function check(mixed $remote, mixed $local)
    {
        $local_file = include WORKING_DIR . '/config/' . $local;
        if ($local_file === true) {
            $local_file = config('global');
        }
        foreach ($remote as $k => $v) {
            if (!isset($local_file[$k])) {
                $this->comment("配置文件 {$local} 需要更新！（当前配置文件缺少 `{$k}` 字段配置）");
                $this->need_update = true;
            }
        }
    }
}
