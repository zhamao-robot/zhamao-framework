<?php


namespace ZM\Annotation\CQ;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class CQAPIResponse
 * @package ZM\Annotation\CQ
 * @Annotation
 * @Target("METHOD")
 */
class CQAPIResponse extends AnnotationBase
{
    /**
     * @var int
     * @Required()
     */
    public $retcode;
}
