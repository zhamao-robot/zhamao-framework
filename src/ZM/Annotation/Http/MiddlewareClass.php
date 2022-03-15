<?php

declare(strict_types=1);

namespace ZM\Annotation\Http;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class MiddlewareClass
 * @Annotation
 * @Target("CLASS")
 */
class MiddlewareClass extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $name = '';
}
