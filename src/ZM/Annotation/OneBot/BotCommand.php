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
        public string $name = '',
        public string $match = '',
        public string $pattern = '',
        public string $regex = '',
        public string $start_with = '',
        public string $end_with = '',
        public string $keyword = '',
        public array $alias = [],
        public string $detail_type = '',
        public string $prefix = '',
        public int $level = 20
    ) {
    }

    public static function make(
        string $name = '',
        string $match = '',
        string $pattern = '',
        string $regex = '',
        string $start_with = '',
        string $end_with = '',
        string $keyword = '',
        array $alias = [],
        string $detail_type = '',
        string $prefix = '',
        int $level = 20
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
