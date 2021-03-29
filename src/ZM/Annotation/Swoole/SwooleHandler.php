<?php


namespace ZM\Annotation\Swoole;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class SwooleHandler
 * @package ZM\Annotation\Swoole
 * @Annotation
 * @Target("ALL")
 */
class SwooleHandler extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $event;
}
