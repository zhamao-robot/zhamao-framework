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
 * Class CQMetaEvent
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class CQMetaEvent extends AnnotationBase implements Level
{
    /**
     * @var string
     * @Required()
     */
    public $meta_event_type;

    /** @var int */
    public $level = 20;

    public function __construct($meta_event_type, $level = 20)
    {
        $this->meta_event_type = $meta_event_type;
        $this->level = $level;
    }

    /**
     * @return int 返回等级
     */
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
}
