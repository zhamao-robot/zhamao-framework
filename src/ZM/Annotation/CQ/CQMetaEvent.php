<?php


namespace ZM\Annotation\CQ;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQMetaEvent
 * @Annotation
 * @Target("ALL")
 * @package ZM\Annotation\CQ
 */
class CQMetaEvent extends AnnotationBase implements Level
{
    /**
     * @var string
     * @Required()
     */
    public $meta_event_type = '';
    /** @var int */
    public $level;

    /**
     * @return mixed
     */
    public function getLevel(): int { return $this->level; }

    /**
     * @param int $level
     */
    public function setLevel($level) {
        $this->level = $level;
    }
}