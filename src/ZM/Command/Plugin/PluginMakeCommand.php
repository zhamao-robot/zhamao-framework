<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use ZM\Bootstrap;
use ZM\Command\Command;
use ZM\Store\FileSystem;
use ZM\Utils\CodeGenerator\PluginGenerator;

#[AsCommand(name: 'plugin:make', description: '创建一个新的插件')]
class PluginMakeCommand extends Command
{
    protected array $bootstrappers = [
        BootStrap\RegisterLogger::class,
        Bootstrap\SetInternalTimezone::class,
        Bootstrap\LoadConfiguration::class,
    ];

    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, '插件名称', null);
        $this->addOption('author', 'a', InputOption::VALUE_OPTIONAL, '作者名称', null);
        $this->addOption('description', 'd', InputOption::VALUE_OPTIONAL, '插件描述', null);
        $this->addOption('plugin-version', null, InputOption::VALUE_OPTIONAL, '插件版本', '1.0.0');
        $this->addOption('type', 'T', InputOption::VALUE_OPTIONAL, '插件类型', null);

        // 下面是 type=psr4 的选项
        $this->addOption('namespace', null, InputOption::VALUE_OPTIONAL, '插件命名空间', null);

        // 下面是辅助用的，和 server:start 一样
        $this->addOption('config-dir', null, InputOption::VALUE_REQUIRED, '指定其他配置文件目录');
    }

    /**
     * {@inheritDoc}
     */
    protected function handle(): int
    {
        $load_dir = config('global.plugin.load_dir');
        if (empty($load_dir)) {
            $load_dir = SOURCE_ROOT_DIR . '/plugins';
        } elseif (FileSystem::isRelativePath($load_dir)) {
            $load_dir = SOURCE_ROOT_DIR . '/' . $load_dir;
        }
        $plugin_dir = zm_dir($load_dir);
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $other_plugins = is_dir($plugin_dir) ? FileSystem::scanDirFiles($plugin_dir, false, true, true) : [];

        // 询问插件名称
        if ($this->input->getArgument('name') === null) {
            $question = new Question('<question>请输入插件名称：</question>');
            $question->setValidator(function ($answer) use ($plugin_dir, $other_plugins) {
                if (empty($answer)) {
                    throw new \RuntimeException('插件名称不能为空');
                }
                if (is_numeric(mb_substr($answer, 0, 1))) {
                    throw new \RuntimeException('插件名称不能以数字开头，且只能包含字母、数字、下划线、短横线');
                }
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $answer)) {
                    throw new \RuntimeException('插件名称只能包含字母、数字、下划线、短横线');
                }
                if (is_dir($plugin_dir . '/' . strtolower($answer))) {
                    throw new \RuntimeException('插件目录已存在，请换个名字');
                }
                foreach ($other_plugins as $dir_name) {
                    $plugin_name = file_exists($plugin_dir . '/' . $dir_name . '/zmplugin.json') ? (json_decode(file_get_contents($plugin_dir . '/' . $dir_name . '/zmplugin.json'), true)['name'] ?? null) : null;
                    if ($plugin_name !== null && $plugin_name === $answer) {
                        throw new \RuntimeException('插件名称已存在，请换个名字');
                    }
                }
                return $answer;
            });
            $this->input->setArgument('name', $helper->ask($this->input, $this->output, $question));
        }

        // 询问插件类型
        if ($this->input->getOption('type') === null) {
            $question = new ChoiceQuestion(
                '<question>请输入要生成的插件结构类型</question>',
                ['file' => 'file 类型为单文件，方便写简单功能', 'psr4' => 'psr4 类型为目录，按照 psr-4 结构生成，同时将生成 composer.json 用来支持自动加载']
            );
            $this->input->setOption('type', $helper->ask($this->input, $this->output, $question));
        }

        if ($this->input->getOption('type') === 'psr4') {
            // 询问命名空间
            if ($this->input->getOption('namespace') === null) {
                $question = new Question('<question>请输入插件命名空间：</question>');
                $question->setValidator(function ($answer) {
                    if (empty($answer)) {
                        throw new \RuntimeException('插件命名空间不能为空');
                    }
                    if (is_numeric(mb_substr($answer, 0, 1))) {
                        throw new \RuntimeException('插件命名空间不能以数字开头，且只能包含字母、数字、反斜线');
                    }
                    // 只能包含字母、数字和反斜线
                    if (!preg_match('/^[a-zA-Z0-9\\\\]+$/', $answer)) {
                        throw new \RuntimeException('插件命名空间只能包含字母、数字、反斜线');
                    }
                    return $answer;
                });
                $this->input->setOption('namespace', $helper->ask($this->input, $this->output, $question));
            }
        }

        $generator = new PluginGenerator($this->input->getArgument('name'), $plugin_dir);
        $generator->generate($this->input->getOptions());

        $this->info('已生成插件：' . $this->input->getArgument('name'));
        $this->info('目录位置：' . zm_dir($plugin_dir . '/' . $this->input->getArgument('name')));
        return self::SUCCESS;
    }
}
