<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class SwooleHandler
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[Attribute(Attribute::TARGET_ALL)]
class SwooleHandler extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $event;

    /** @var string */
    public $params = '';

    public function __construct($event, $params = '')
    {
        $this->event = $event;
        $this->params = $params;
    }
}
