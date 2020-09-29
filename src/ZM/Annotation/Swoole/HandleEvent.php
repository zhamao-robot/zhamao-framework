<?php


namespace ZM\Annotation\Swoole;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class HandleEvent
 * @package ZM\Annotation\Swoole
 * @Annotation
 * @Target("METHOD")
 */
class HandleEvent extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $event;
}
