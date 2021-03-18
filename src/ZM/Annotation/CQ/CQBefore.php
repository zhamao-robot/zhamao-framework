<?php


namespace ZM\Annotation\CQ;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQBefore
 * @Annotation
 * @Target("METHOD")
 * @package ZM\Annotation\CQ
 */
class CQBefore extends AnnotationBase implements Level
{
    /**
     * @var string
     * @Required()
     */
    public $cq_event;

    public $level = 20;

    /**
     * @return mixed
     */
    public function getLevel(): int {
        return $this->level;
    }

    /**
     * @param mixed $level
     */
    public function setLevel($level) {
        $this->level = $level;
    }

}