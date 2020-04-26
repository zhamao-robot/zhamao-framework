<?php


namespace ZM\Annotation\Http;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class Middleware
 * @package ZM\Annotation\Http
 * @Annotation
 * @Target("ALL")
 */
class Middleware extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $middleware;
}
