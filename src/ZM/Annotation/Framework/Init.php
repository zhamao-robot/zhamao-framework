<?php

declare(strict_types=1);

namespace ZM\Annotation\Framework;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class Init
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 * @since 3.0.0
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class Init extends AnnotationBase
{
    /** @var int */
    public $worker = 0;

    public function __construct(int $worker = 0)
    {
        $this->worker = $worker;
    }
}
