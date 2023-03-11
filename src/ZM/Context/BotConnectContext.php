<?php

declare(strict_types=1);

namespace ZM\Context;

use ZM\Context\Trait\BotActionTrait;

/**
 * 机器人裸连接的上下文
 */
class BotConnectContext
{
    use BotActionTrait;

    protected ?array $self = null;

    public function __construct(private int $flag, private int $fd)
    {
    }

    public function getFd(): int
    {
        return $this->fd;
    }

    public function getFlag(): int
    {
        return $this->flag;
    }
}
