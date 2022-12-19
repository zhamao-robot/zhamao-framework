<?php

declare(strict_types=1);

namespace ZM\Annotation\OneBot;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * 机器人相关事件注解
 *
 * @Annotation
 * @Target("METHOD")
 * @NamedArgumentConstructor()
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BotEvent extends AnnotationBase
{
    public function __construct(public ?string $type = null, public ?string $detail_type = null, public ?string $impl = null, public ?string $platform = null, public ?string $self_id = null, public ?string $sub_type = null)
    {
    }

    public static function make(
        ?string $type = null,
        ?string $detail_type = null,
        ?string $impl = null,
        ?string $platform = null,
        ?string $self_id = null,
        ?string $sub_type = null
    ): BotEvent {
        return new static(...func_get_args());
    }
}
