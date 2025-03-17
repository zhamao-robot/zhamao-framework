<?php

declare(strict_types=1);

namespace ZM\Annotation\Middleware;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\ErgodicAnnotation;

/**
 * Class Middleware
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_ALL)]
class Middleware extends AnnotationBase implements ErgodicAnnotation
{
    /**
     * @param mixed $name
     */
    public function __construct(
        /**
         * @Required()
         */
        public $name,
        public array $args = []
    ) {}
}
