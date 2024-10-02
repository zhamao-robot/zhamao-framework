<?php

declare(strict_types=1);

namespace ZM\Annotation\Framework;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class ZMSetup
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 * @since 3.0.0
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class Setup extends AnnotationBase {}
