<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class OnTick
 * @Annotation
 * @NamedArgumentConstructor()
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

    public function __construct($tick_ms, $worker_id = 0)
    {
        $this->tick_ms = $tick_ms;
        $this->worker_id = $worker_id;
    }
}
