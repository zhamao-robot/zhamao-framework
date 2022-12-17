<?php

declare(strict_types=1);

namespace ZM\Command\BotCraft;

use Symfony\Component\Console\Attribute\AsCommand;
use ZM\Command\Command;

#[AsCommand(name: 'bc:make', description: '生成插件')]
class BotCraftCommand extends Command
{
    protected function handle(): int
    {
        // TODO: Implement handle() method.
        return self::SUCCESS;
    }
}
