<?php

declare(strict_types=1);

namespace ZM\Annotation\Framework;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class Init
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 * @since 3.0.0
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class Init extends AnnotationBase implements Level
{
    public function __construct(public int $worker = 0, public int $level = 20)
    {
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }
}
