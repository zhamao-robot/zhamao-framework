<?php

declare(strict_types=1);

namespace Custom\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\CustomAnnotation;

/**
 * Class CustomAnnotation
 * @Annotation
 * @Target("ALL")
 */
class Example extends AnnotationBase implements CustomAnnotation
{
    /** @var string */
    public $str = '';
}
