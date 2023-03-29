<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use ZM\Plugin\PluginManager;
use ZM\Utils\ZMUtil;

#[AsCommand(name: 'plugin:remove', description: '卸载一个安装好的外部插件')]
class PluginRemoveCommand extends PluginCommand
{
    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, '插件名称');
    }

    /**
     * {@inheritDoc}
     */
    protected function handle(): int
    {
        $plugin_name = $this->input->getArgument('name');

        // 过滤非 Composer 部署的插件
        foreach (PluginManager::getPlugins() as $meta) {
            if ($meta->getName() === $plugin_name && $meta->getPluginType() !== ZM_PLUGIN_TYPE_COMPOSER) {
                $this->error('插件卸载功能仅可卸载通过 Git 或 Composer 仓库安装的插件，不支持源码插件和内置插件！');
                return static::FAILURE;
            }
        }

        $composer = ZMUtil::getComposerMetadata();
        if (!isset($composer['require'][$plugin_name])) {
            $this->error("插件 {$plugin_name} 不存在！");
            return static::FAILURE;
        }

        $env = ZMUtil::getComposerExecutable();
        passthru("{$env} remove " . escapeshellarg($plugin_name), $code);
        if ($code !== 0) {
            $this->error('插件卸载失败！');
            return static::FAILURE;
        }

        // 如果是 Git 安装的，需要遍历找一下有没有 repo
        foreach ($composer['repositories'] as $k => $v) {
            if (isset($v['.belongs']) && $v['.belongs'] === $plugin_name) {
                array_splice($composer['repositories'], $k, 1);
                break;
            }
        }
        ZMUtil::putComposerMetadata(content: $composer);
        $this->info('插件卸载成功！');
        return static::SUCCESS;
    }
}
