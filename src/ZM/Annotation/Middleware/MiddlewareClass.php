<?php

declare(strict_types=1);

namespace ZM\Annotation\Middleware;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class MiddlewareClass
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class MiddlewareClass extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $name = '';

    public function __construct($name)
    {
        $this->name = $name;
    }
}
