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
 * Class OnOpenEvent
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class OnOpenEvent extends OnSwooleEventBase
{
    /**
     * @var string
     */
    public $connect_type = 'default';

    public function __construct($connect_type = 'default', $rule = '', $level = 20)
    {
        $this->connect_type = $connect_type;
        $this->rule = $rule;
        $this->level = $level;
    }
}
