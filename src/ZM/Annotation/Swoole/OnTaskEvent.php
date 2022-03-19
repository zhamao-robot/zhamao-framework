<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class OnTaskEvent
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class OnTaskEvent extends OnSwooleEventBase
{
    public function __construct($rule = '', $level = 20)
    {
        $this->rule = $rule;
        $this->level = $level;
    }
}
