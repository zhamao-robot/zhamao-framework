<?php


namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class OnWorkerStart
 * @package ZM\Annotation\Swoole
 * @Annotation
 * @Target("ALL")
 */
class OnStart extends AnnotationBase
{
    /**
     * @var int
     */
    public $worker_id = 0;
}
