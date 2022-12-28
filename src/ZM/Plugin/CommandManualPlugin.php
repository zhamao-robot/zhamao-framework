<?php

declare(strict_types=1);

namespace ZM\Plugin;

use ZM\Annotation\AnnotationParser;
use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\CommandArgument;
use ZM\Annotation\OneBot\CommandHelp;
use ZM\Context\BotContext;

/**
 * CommandManual 插件
 *
 * 用以生成、处理指令帮助
 */
class CommandManualPlugin extends ZMPlugin
{
    private array $template = [
        ['type' => 'command', 'header' => false, 'indent' => false],
        ['type' => 'description', 'header' => false, 'indent' => false],
        ['type' => 'usage', 'header' => false, 'indent' => false],
        ['type' => 'arguments', 'header' => '可用参数：', 'indent' => true],
        ['type' => 'examples', 'header' => '使用示例：', 'indent' => true],
    ];

    /**
     * 命令（帮助）列表，键为命令名，值为命令帮助
     *
     * @var array<string, string>
     */
    private array $command_list = [];

    public function __construct(AnnotationParser $parser)
    {
        parent::__construct(__DIR__);

        if (config('command_manual.template') !== null) {
            $this->template = config('command_manual.template');
        }

        $parser->addSpecialParser(BotCommand::class, [$this, 'parseBotCommand']);
        $parser->addSpecialParser(CommandHelp::class, fn () => false);

        $this->addBotCommand(
            BotCommand::make('help', 'help')
                ->withArgument('command', '要查询的指令名', required: true)
                ->on([$this, 'onHelp'])
        );
        logger()->info('CommandManualPlugin loaded.');
    }

    /**
     * 解析 BotCommand 的参数和帮助
     *
     * @param BotCommand $command                 命令对象
     * @param null|array $same_method_annotations 同一个方法的所有注解
     */
    public function parseBotCommand(BotCommand $command, ?array $same_method_annotations = null): ?bool
    {
        if ($same_method_annotations) {
            foreach ($same_method_annotations as $v) {
                if ($v instanceof CommandHelp) {
                    $help = $v;
                    break;
                }
            }
        }
        $help = $help ?? new CommandHelp('', '', '');
        $section = '';
        foreach ($this->template as $v) {
            $content = $this->getSectionContent($command, $v['type'], $help);
            $this->addSection($section, $content, $v);
        }
        $this->command_list[$command->name] = $section;
        return true;
    }

    public function onHelp(BotContext $context): void
    {
        $command = $context->getParam('command');
        if (isset($this->command_list[$command])) {
            $context->reply($this->command_list[$command]);
        } else {
            $context->reply('未找到指令 ' . $command);
        }
    }

    private function addSection(string &$section, string $content, array $options): void
    {
        if (!$content) {
            return;
        }
        if ($options['header']) {
            $section .= $options['header'] . PHP_EOL;
        }
        if ($options['indent']) {
            $content = '    ' . str_replace(PHP_EOL, PHP_EOL . '    ', $content);
            $content = rtrim($content);
        }
        $section .= $content . PHP_EOL;
    }

    private function getSectionContent(BotCommand $command, string $type, CommandHelp $help): string
    {
        switch ($type) {
            case 'command':
                return $command->name;
            case 'description':
                return $help->description;
            case 'usage':
                return $help->usage;
            case 'arguments':
                $ret = '';
                foreach ($command->getArguments() as $argument) {
                    /* @var CommandArgument $argument */
                    $ret .= $argument->name . ' - ' . $argument->description . PHP_EOL;
                }
                return $ret;
            case 'examples':
                return $help->example;
            default:
                return '';
        }
    }
}
