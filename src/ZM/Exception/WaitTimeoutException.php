<?php

declare(strict_types=1);

namespace ZM\Exception;

use OneBot\V12\Object\ActionResponse;
use OneBot\V12\Object\MessageSegment;
use OneBot\V12\Object\OneBotEvent;

class WaitTimeoutException extends ZMException
{
    private ?array $timeout_prompt;

    public function __construct(
        public mixed $module,
        string|\MessageSegment|array|\Stringable $timeout_prompt = '',
        private ?ActionResponse $prompt_response = null,
        private ?OneBotEvent $user_event = null,
        private int $prompt_option = ZM_PROMPT_NONE,
        \Throwable $previous = null
    ) {
        parent::__construct('wait timeout!', 0, $previous);
        if ($timeout_prompt === '') {
            $this->timeout_prompt = null;
        } elseif ($timeout_prompt instanceof MessageSegment) {
            $this->timeout_prompt = [$timeout_prompt];
        } elseif (is_string($timeout_prompt) || $timeout_prompt instanceof \Stringable) {
            $this->timeout_prompt = [strval($timeout_prompt)];
        } else {
            $this->timeout_prompt = $timeout_prompt;
        }
    }

    public function getTimeoutPrompt(): ?array
    {
        return $this->timeout_prompt;
    }

    public function getPromptResponse(): ?ActionResponse
    {
        return $this->prompt_response;
    }

    public function getUserEvent(): ?OneBotEvent
    {
        return $this->user_event;
    }

    public function getPromptOption(): int
    {
        return $this->prompt_option;
    }
}
