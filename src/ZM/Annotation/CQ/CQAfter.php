<?php

declare(strict_types=1);

namespace ZM\Annotation\CQ;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQAfter
 * @Annotation
 * @Target("METHOD")
 */
class CQAfter extends AnnotationBase implements Level
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
    public function getLevel()
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
