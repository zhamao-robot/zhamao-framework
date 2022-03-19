<?php

declare(strict_types=1);

namespace Custom\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\CustomAnnotation;

/**
 * Class CustomAnnotation
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class Example extends AnnotationBase implements CustomAnnotation
{
    /** @var string */
    public $str = '';

    public function __construct($str = '')
    {
        $this->str = $str;
    }
}
