<?php


namespace Custom\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\CustomAnnotation;

/**
 * Class CustomAnnotation
 * @Annotation
 * @Target("ALL")
 * @package Custom\Annotation
 */
class Example extends AnnotationBase implements CustomAnnotation
{
    /** @var string */
    public $str = '';
}
