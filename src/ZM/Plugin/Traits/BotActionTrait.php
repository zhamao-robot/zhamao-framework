<?php

declare(strict_types=1);

namespace ZM\Plugin\Traits;

use ZM\Annotation\OneBot\BotAction;

trait BotActionTrait
{
    protected array $bot_actions = [];

    public function onBotAction(BotAction $bot_action_annotation): void
    {
        $this->bot_actions[] = $bot_action_annotation;
    }

    /**
     * @internal
     */
    public function getBotActions(): array
    {
        return $this->bot_actions;
    }
}
