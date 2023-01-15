<?php

declare(strict_types=1);

namespace ZM\Plugin\Traits;

use ZM\Annotation\OneBot\BotEvent;

trait BotEventTrait
{
    /** @var array 机器人事件列表 */
    protected array $bot_events = [];

    /**
     * 添加一个 OneBot 机器人事件
     * @param BotEvent $event BotEvent 注解对象
     */
    public function addBotEvent(BotEvent $event): void
    {
        $this->bot_events[] = $event;
    }

    public function getBotEvents(): array
    {
        return $this->bot_events;
    }
}
