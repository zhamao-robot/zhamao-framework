<?php


namespace ZM\Annotation\Swoole;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class OnTick
 * @package ZM\Annotation\Swoole
 * @Annotation
 * @Target("METHOD")
 * @since 1.2
 */
class OnTick extends AnnotationBase
{
    /**
     * @var int
     * @Required()
     */
    public $tick_ms;

    /**
     * @var int
     */
    public $worker_id = 0;
}
