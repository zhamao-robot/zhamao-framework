<?php

declare(strict_types=1);

namespace ZM\Annotation\Http;

use Doctrine\Common\Annotations\Annotation\Target;
use Exception;
use ZM\Annotation\AnnotationBase;

/**
 * Class HandleException
 * @Annotation
 * @Target("METHOD")
 */
class HandleException extends AnnotationBase
{
    /**
     * @var string
     */
    public $class_name = Exception::class;
}
