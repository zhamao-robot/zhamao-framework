<?php


namespace ZM\Annotation\Http;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class MiddlewareClass
 * @package ZM\Annotation\Http
 * @Annotation
 * @Target("CLASS")
 */
class MiddlewareClass extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $name = '';
}
