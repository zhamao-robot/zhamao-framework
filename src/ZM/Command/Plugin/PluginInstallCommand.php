<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use ZM\Plugin\Strategy\ComposerStrategy;
use ZM\Plugin\Strategy\GitStrategy;
use ZM\Store\FileSystem;

#[AsCommand(name: 'plugin:install', description: '从 GitHub 或其他 Git 源码托管站安装插件')]
class PluginInstallCommand extends PluginCommand
{
    protected function configure()
    {
        $this->addArgument('address', InputArgument::REQUIRED, '插件地址');
        $this->addOption('github-token', null, InputOption::VALUE_REQUIRED, '提供的 GitHub Token');
    }

    /**
     * {@inheritDoc}
     */
    protected function handle(): int
    {
        $addr = $this->input->getArgument('address');
        $this->plugin_dir = FileSystem::isRelativePath(config('global.plugin.load_dir', 'plugins')) ? (WORKING_DIR . '/' . config('global.plugin.load_dir', 'plugins')) : config('global.plugin.load_dir', 'plugins');

        // 先检查传入的参数是什么类型。如果是 a/b 类型则为 composer 包
        if (count(explode('/', $addr)) === 2) {
            $st = new ComposerStrategy($addr, $this->plugin_dir, logger: $this);
        } elseif ((parse_url($addr)['scheme'] ?? null) !== null) {
            $st = new GitStrategy($addr, $this->plugin_dir, logger: $this);
        } else {
            $this->error('无法检测输入要安装插件的链接或名字，请检查后再试！');
            return static::FAILURE;
        }

        if ($token = $this->input->getOption('github-token')) {
            $option = ['github-token' => $token];
        } else {
            $option = [];
        }
        // 然后调用安装并看是否成功
        try {
            if (!$st->install($option)) {
                $this->error('插件安装失败，' . $st->getError());
                return static::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('插件安装失败: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return static::FAILURE;
        }

        $this->info('插件 ' . $st->getInstalledName() . ' 安装成功！');
        return static::SUCCESS;
    }
}
