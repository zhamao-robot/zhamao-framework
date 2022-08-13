<?php

declare(strict_types=1);

namespace ZM\Annotation\OneBot;

use Attribute;
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
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class BotCommand extends AnnotationBase implements Level
{
    /** @var string */
    public $name = '';

    /** @var string */
    public $match = '';

    /** @var string */
    public $pattern = '';

    /** @var string */
    public $regex = '';

    /** @var string */
    public $start_with = '';

    /** @var string */
    public $end_with = '';

    /** @var string */
    public $keyword = '';

    /** @var string[] */
    public $alias = [];

    /** @var string */
    public $message_type = '';

    /** @var string */
    public $user_id = '';

    /** @var string */
    public $group_id = '';

    /** @var int */
    public $level = 20;

    /** @var array */
    private $arguments = [];

    public function __construct(
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
    ) {
        $this->name = $name;
        $this->match = $match;
        $this->pattern = $pattern;
        $this->regex = $regex;
        $this->start_with = $start_with;
        $this->end_with = $end_with;
        $this->keyword = $keyword;
        $this->alias = $alias;
        $this->message_type = $message_type;
        $this->user_id = $user_id;
        $this->group_id = $group_id;
        $this->level = $level;
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
     * @return $this
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
