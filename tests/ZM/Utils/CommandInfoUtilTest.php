<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use ZM\Annotation\CQ\CommandArgument;
use ZM\Annotation\CQ\CQCommand;
use ZM\Event\EventManager;
use ZM\Utils\CommandInfoUtil;

/**
 * @internal
 */
class CommandInfoUtilTest extends TestCase
{
    /**
     * @var CommandInfoUtil
     */
    private static $util;

    /**
     * @var string
     */
    private static $command_id;

    public static function setUpBeforeClass(): void
    {
        $cmd = new CQCommand('测试命令');
        $cmd->class = self::class;
        $cmd->method = __FUNCTION__;

        $args = [
            new CommandArgument('文本', '一个神奇的文本', 'string', true),
            new CommandArgument('数字', '一个神奇的数字', 'int', false, '', '233'),
        ];

        self::$command_id = "{$cmd->class}@{$cmd->method}";

        EventManager::$events[CQCommand::class] = [];
        EventManager::$event_map = [];
        EventManager::addEvent(CQCommand::class, $cmd);
        EventManager::$event_map[$cmd->class][$cmd->method] = $args;

        self::$util = resolve(CommandInfoUtil::class);
    }

    public function testGet(): void
    {
        $commands = self::$util->get();
        $this->assertIsArray($commands);
        $this->assertCount(1, $commands);
        $this->assertArrayHasKey(self::$command_id, $commands);
    }

    public function testGetHelp(): void
    {
        $help = self::$util->getHelp(self::$command_id);
        $this->assertIsString($help);
        $this->assertNotEmpty($help);

        $expected = <<<'EOF'
测试命令 <文本: string> [数字: number = 233]
作者很懒，啥也没说
文本；一个神奇的文本
数字；一个神奇的数字
EOF;
        $this->assertEquals($expected, $help);
    }
}
