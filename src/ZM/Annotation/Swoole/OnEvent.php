<?php


namespace ZM\Annotation\Swoole;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class OnEvent
 * @package ZM\Annotation\Swoole
 * @Annotation
 * @Target("METHOD")
 */
class OnEvent extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $event;
}
