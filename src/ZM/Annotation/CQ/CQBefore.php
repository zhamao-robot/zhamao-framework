<?php

declare(strict_types=1);

namespace ZM\Annotation\CQ;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQBefore
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class CQBefore extends AnnotationBase implements Level
{
    /**
     * @var string
     * @Required()
     */
    public $cq_event;

    public $level = 20;

    public function __construct($cq_event, $level = 20)
    {
        $this->cq_event = $cq_event;
        $this->level = $level;
    }

    /**
     * @return mixed
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param mixed $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }
}
