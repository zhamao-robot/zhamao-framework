<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use ZM\Exception\FileSystemException;
use ZM\Store\FileSystem;
use ZM\Utils\CodeGenerator\PluginGenerator;

#[AsCommand(name: 'plugin:make', description: '创建一个新的插件')]
class PluginMakeCommand extends PluginCommand
{
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, '插件名称', null);
        $this->addOption('author', 'a', InputOption::VALUE_OPTIONAL, '作者名称', null);
        $this->addOption('description', null, InputOption::VALUE_OPTIONAL, '插件描述', null);
        $this->addOption('plugin-version', null, InputOption::VALUE_OPTIONAL, '插件版本', '1.0.0');
        $this->addOption('type', 'T', InputOption::VALUE_OPTIONAL, '插件类型', null);

        // 下面是 type=psr4 的选项
        $this->addOption('namespace', null, InputOption::VALUE_OPTIONAL, '插件命名空间', null);
    }

    /**
     * {@inheritDoc}
     * @throws FileSystemException
     */
    protected function handle(): int
    {
        $load_dir = config('global.plugin.load_dir');
        if (empty($load_dir)) {
            $load_dir = SOURCE_ROOT_DIR . '/plugins';
        } elseif (FileSystem::isRelativePath($load_dir)) {
            $load_dir = SOURCE_ROOT_DIR . '/' . $load_dir;
        }
        $this->plugin_dir = zm_dir($load_dir);

        // 询问插件名称
        if ($this->input->getArgument('name') === null) {
            $this->questionWithArgument('name', '请输入插件名称（插件名称格式为"所有者/插件名"，例如"foobar/demo-plugin"）', [$this, 'validatePluginName']);
        }

        // 询问插件类型
        if ($this->input->getOption('type') === null) {
            $this->choiceWithOption('type', '请输入要生成的插件结构类型', [
                'file' => 'file 类型为单文件，方便写简单功能',
                'psr4' => 'psr4 类型为目录，按照 psr-4 结构生成，同时将生成 composer.json 用来支持自动加载（推荐）',
            ]);
        }

        if ($this->input->getOption('type') === 'psr4') {
            // 询问命名空间
            if ($this->input->getOption('namespace') === null) {
                $default_namespace = explode('/', $this->input->getArgument('name'))[0];
                $this->questionWithOption('namespace', '请输入插件命名空间，输入则使用自定义，回车默认使用 [' . $default_namespace . ']：', [$this, 'validateNamespace'], $default_namespace);
            }
        }

        $generator = new PluginGenerator($this->input->getArgument('name'), $this->plugin_dir);
        $dir = $generator->generate($this->input->getOptions());

        $this->info('已生成插件：' . $this->input->getArgument('name'));
        $this->info('目录位置：' . $dir);
        return self::SUCCESS;
    }
}
