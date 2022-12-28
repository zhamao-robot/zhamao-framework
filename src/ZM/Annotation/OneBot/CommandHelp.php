<?php

declare(strict_types=1);

namespace ZM\Annotation\OneBot;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * 机器人指令帮助注解
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class CommandHelp extends AnnotationBase
{
    public function __construct(
        public string $description,
        public string $usage,
        public string $example,
    ) {
    }

    public static function make(
        string $description,
        string $usage,
        string $example,
    ): CommandHelp {
        return new static(...func_get_args());
    }
}
