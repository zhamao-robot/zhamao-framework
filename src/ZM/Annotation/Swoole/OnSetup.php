<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class ZMSetup
 * @Annotation
 * @Target("METHOD")
 */
class OnSetup extends AnnotationBase
{
}
