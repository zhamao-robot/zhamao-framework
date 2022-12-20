<?php

declare(strict_types=1);

namespace ZM\Annotation\OneBot;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;
use ZM\Exception\InvalidArgumentException;
use ZM\Exception\ZMKnownException;

/**
 * Class BotCommand
 * 机器人指令注解
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class BotCommand extends AnnotationBase implements Level
{
    private array $arguments = [];

    /**
     * @param string[] $alias
     */
    public function __construct(
        public $name = '',
        public $match = '',
        public $pattern = '',
        public $regex = '',
        public $start_with = '',
        public $end_with = '',
        public $keyword = '',
        public $alias = [],
        public $detail_type = '',
        public $user_id = '',
        public $group_id = '',
        public $level = 20
    ) {
    }

    public static function make(
        $name = '',
        $match = '',
        $pattern = '',
        $regex = '',
        $start_with = '',
        $end_with = '',
        $keyword = '',
        $alias = [],
        $message_type = '',
        $user_id = '',
        $group_id = '',
        $level = 20
    ): BotCommand {
        return new static(...func_get_args());
    }

    /**
     * @throws InvalidArgumentException
     * @throws ZMKnownException
     */
    public function withArgument(
        string $name,
        string $description = '',
        string $type = 'string',
        bool $required = false,
        string $prompt = '',
        string $default = '',
        int $timeout = 60,
        int $error_prompt_policy = 1
    ): BotCommand {
        $this->arguments[] = new CommandArgument($name, $description, $type, $required, $prompt, $default, $timeout, $error_prompt_policy);
        return $this;
    }

    public function withArgumentObject(CommandArgument $argument): BotCommand
    {
        $this->arguments[] = $argument;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
