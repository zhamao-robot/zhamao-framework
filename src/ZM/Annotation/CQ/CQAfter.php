<?php


namespace ZM\Annotation\CQ;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class CQAfter
 * @Annotation
 * @Target("METHOD")
 * @package ZM\Annotation\CQ
 */
class CQAfter extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $cq_event;
}