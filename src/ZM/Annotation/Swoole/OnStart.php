<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class OnWorkerStart
 * @Annotation
 * @Target("METHOD")
 */
class OnStart extends AnnotationBase
{
    /**
     * @var int
     */
    public $worker_id = 0;
}
