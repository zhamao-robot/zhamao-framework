<?php

declare(strict_types=1);

namespace ZM\Annotation\OneBot;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * 机器人相关事件注解
 *
 * @Annotation
 * @Target("METHOD")
 * @NamedArgumentConstructor()
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BotEvent extends AnnotationBase implements Level
{
    public function __construct(public ?string $type = null, public ?string $detail_type = null, public ?string $sub_type = null, public int $level = 20)
    {
    }

    public static function make(
        ?string $type = null,
        ?string $detail_type = null,
        ?string $sub_type = null,
        int $level = 20,
    ): BotEvent {
        return new static(...func_get_args());
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }
}
