<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 * @since 2.7.0
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class OnManagerStartEvent extends OnSwooleEventBase
{
    public function __construct($rule = '', $level = 20)
    {
        $this->rule = $rule;
        $this->level = $level;
    }
}
