<?php

declare(strict_types=1);

namespace ZM\Annotation\Middleware;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use Exception;
use ZM\Annotation\AnnotationBase;

/**
 * Class HandleException
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class HandleException extends AnnotationBase
{
    /**
     * @var string
     */
    public $class_name = Exception::class;

    public function __construct($class_name = Exception::class)
    {
        $this->class_name = $class_name;
    }
}
