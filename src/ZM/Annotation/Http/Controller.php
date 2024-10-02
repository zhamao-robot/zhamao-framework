<?php

declare(strict_types=1);

namespace ZM\Annotation\Http;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\ErgodicAnnotation;

/**
 * Class Controller
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
class Controller extends AnnotationBase implements ErgodicAnnotation
{
    public function __construct(
        /**
         * @Required()
         */
        public string $prefix
    ) {}
}
