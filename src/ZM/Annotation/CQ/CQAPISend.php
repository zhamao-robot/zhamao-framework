<?php


namespace ZM\Annotation\CQ;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQAPISend
 * @package ZM\Annotation\CQ
 * @Annotation
 * @Target("METHOD")
 */
class CQAPISend extends AnnotationBase implements Level
{
    /**
     * @var string
     */
    public $action = "";

    /**
     * @var bool
     */
    public $with_result = false;

    public $level = 20;

    /**
     * @return mixed
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @param mixed $level
     */
    public function setLevel($level) {
        $this->level = $level;
    }
}
