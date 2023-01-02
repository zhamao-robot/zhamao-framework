<?php

declare(strict_types=1);

namespace ZM\Plugin\CommandManual;

use ZM\Annotation\AnnotationBase;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\CommandHelp;
use ZM\Context\BotContext;
use ZM\Plugin\ZMPlugin;

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
     * 命令手册工厂，键为优先级，值为工厂
     *
     * @var array<int, array|callable|string>
     */
    private static array $manual_factories = [
        10 => StaticManualFactory::class,
    ];

    /**
     * 命令列表，键为命令名，值为命令实例
     *
     * @var array<string, BotCommand>
     */
    private array $commands = [];

    /**
     * 命令邻近注解，键为命令名，值为邻近注解数组
     *
     * @var array<string, AnnotationBase[]>
     */
    private array $adjacent_annotations = [];

    public function __construct(AnnotationParser $parser)
    {
        parent::__construct(__DIR__);

        if (config('command_manual.template') !== null) {
            $this->template = config('command_manual.template');
        }

        $parser->addSpecialParser(BotCommand::class, [$this, 'parseBotCommand']);
        $parser->addSpecialParser(CommandHelp::class, fn () => false);

        $this->addBotCommand(
            BotCommand::make('help', 'help', level: 10)
                ->withArgument('command', '要查询的指令名', required: true)
                ->on([$this, 'onHelp'])
        );
    }

    /**
     * 添加命令手册工厂
     *
     * @param array|callable|string $factory  工厂
     * @param int                   $priority 优先级
     */
    public static function addManualFactory(array|callable|string $factory, int $priority = 20): void
    {
        self::$manual_factories[$priority] = $factory;
        logger()->debug('命令手册工厂已添加 {factory} 优先级 {priority}', compact('factory', 'priority'));
    }

    /**
     * 解析 BotCommand 的参数和帮助
     *
     * @param BotCommand $command              命令对象
     * @param null|array $adjacent_annotations 同一个方法的所有注解
     */
    public function parseBotCommand(BotCommand $command, ?array $adjacent_annotations = null): ?bool
    {
        $this->commands[$command->name] = $command;
        $this->adjacent_annotations[$command->name] = $adjacent_annotations ?? [];
        return true;
    }

    /**
     * 命令手册获取命令
     *
     * @param BotContext $context 上下文
     */
    public function onHelp(BotContext $context): void
    {
        $command_name = $context->getParam('command');
        $command = $this->commands[$command_name] ?? null;
        if ($command === null) {
            $context->reply('命令不存在');
            return;
        }
        $adjacent_annotations = $this->adjacent_annotations[$command_name] ?? [];

        // 遍历工厂，直到找到一个返回非空的工厂
        foreach (self::$manual_factories as $factory) {
            $manual = container()->call(
                $factory,
                [
                    'context' => $context,
                    'command' => $command,
                    'template' => $this->template,
                    'adjacent_annotations' => $adjacent_annotations,
                ]
            );
            if ($manual !== null) {
                $context->reply($manual);
                return;
            }
        }
        $context->reply("未找到指令 {$command} 的帮助");
    }
}
