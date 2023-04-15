<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use ZM\Bootstrap;
use ZM\Command\Command;
use ZM\Plugin\PluginManager;

abstract class PluginCommand extends Command
{
    /** @var null|string 动态插件和 Phar 插件的加载目录 */
    protected ?string $plugin_dir = null;

    private static bool $loaded = false;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        if (!self::$loaded) {
            $this->emitBootstrap(Bootstrap\LoadPlugins::class);
            self::$loaded = true;
        }
    }

    /**
     * 插件名称合规验证器
     */
    public function validatePluginName(string $answer): string
    {
        if (empty($answer)) {
            throw new \RuntimeException('插件名称不能为空');
        }
        if (is_numeric(mb_substr($answer, 0, 1))) {
            throw new \RuntimeException('插件名称不能以数字开头，且只能包含字母、数字、下划线、短横线');
        }
        if (!preg_match('/^[\/a-zA-Z0-9_-]+$/', $answer)) {
            throw new \RuntimeException('插件名称只能包含字母、数字、下划线、短横线');
        }
        $exp = explode('/', $answer);
        if (count($exp) !== 2) {
            throw new \RuntimeException('插件名称必须为"组织或所有者/插件名称"的格式，且只允许有一个斜杠分割两者');
        }
        if ($exp[0] === 'zhamao') {
            throw new \RuntimeException('插件所有者或组织名不可以为"zhamao"，请换个名字');
        }
        if ($exp[0] === '' || $exp[1] === '') {
            throw new \RuntimeException('插件所有者或组织名、插件名称均不可为空');
        }
        if (PluginManager::isPluginExists($answer)) {
            throw new \RuntimeException('名称为 ' . $answer . ' 的插件已存在，请换个名字');
        }
        if (is_dir(zm_dir($this->plugin_dir . '/' . $exp[1]))) {
            throw new \RuntimeException('本插件名称的插件开发目录已经有相同名称，请先将同名插件的目录名修改，或修改本插件名称');
        }
        return $answer;
    }

    /**
     * 命名空间合规验证器
     */
    public function validateNamespace(string $answer): string
    {
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
    }

    /**
     * 添加 Question 来询问缺失的参数
     *
     * @param string   $name      参数名称
     * @param string   $question  问题
     * @param callable $validator 验证器
     */
    protected function questionWithArgument(string $name, string $question, callable $validator): void
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question('<question>' . $question . '</question>');
        $question->setValidator($validator);
        $this->input->setArgument($name, $helper->ask($this->input, $this->output, $question));
    }

    /**
     * 添加 Question 来询问缺失的参数
     *
     * @param string   $name      参数名称
     * @param string   $question  问题
     * @param callable $validator 验证器
     */
    protected function questionWithOption(string $name, string $question, callable $validator, string $default = null): void
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question('<question>' . $question . '</question>', $default);
        $question->setValidator($validator);
        $this->input->setOption($name, $helper->ask($this->input, $this->output, $question));
    }

    /**
     * 添加选择题来询问缺失的参数
     *
     * @param string $name      可选参数名称
     * @param string $question  问题
     * @param array  $selection 选项（K-V 类型）
     */
    protected function choiceWithOption(string $name, string $question, array $selection): void
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('<question>' . $question . '</question>', $selection);
        $this->input->setOption($name, $helper->ask($this->input, $this->output, $question));
    }

    protected function getTypeDisplayName(int $type): string
    {
        return match ($type) {
            ZM_PLUGIN_TYPE_NATIVE => '内部',
            ZM_PLUGIN_TYPE_PHAR => '<comment>Phar</comment>',
            ZM_PLUGIN_TYPE_SOURCE => '<fg=gray>源码</>',
            ZM_PLUGIN_TYPE_COMPOSER => '<info>Composer</info>',
            default => '未知模式'
        };
    }
}
