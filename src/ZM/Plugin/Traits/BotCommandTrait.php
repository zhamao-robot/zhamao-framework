<?php

declare(strict_types=1);

namespace ZM\Plugin\Traits;

use ZM\Annotation\OneBot\BotCommand;

trait BotCommandTrait
{
    /** @var array 机器人指令列表 */
    protected array $bot_commands = [];

    /**
     * 添加一个 OneBot 机器人命令
     *
     * @param BotCommand $command BotCommand 注解对象
     */
    public function addBotCommand(BotCommand $command): void
    {
        $this->bot_commands[] = $command;
    }

    public function getBotCommands(): array
    {
        return $this->bot_commands;
    }
}
