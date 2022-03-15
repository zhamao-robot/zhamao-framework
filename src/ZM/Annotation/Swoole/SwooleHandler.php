<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class SwooleHandler
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
