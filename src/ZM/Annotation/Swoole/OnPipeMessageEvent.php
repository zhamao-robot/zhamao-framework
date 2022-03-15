<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class OnPipeMessageEvent
 * @Annotation
 * @Target("METHOD")
 */
class OnPipeMessageEvent extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $action;
}
