<?php

declare(strict_types=1);

namespace ZM\Annotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Closed
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_ALL)]
class Closed extends AnnotationBase {}
