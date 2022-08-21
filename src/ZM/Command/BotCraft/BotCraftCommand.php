<?php

declare(strict_types=1);

namespace ZM\Command\BotCraft;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TODO: 用于从命令行创建插件
 */
class BotCraftCommand extends Command
{
    protected static $defaultName = 'bc:make';

    public function execute(InputInterface $input, OutputInterface $output)
    {
        return 0;
    }
}
