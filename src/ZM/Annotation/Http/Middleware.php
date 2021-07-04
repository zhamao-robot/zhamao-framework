<?php


namespace ZM\Annotation\Http;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\ErgodicAnnotation;

/**
 * Class Middleware
 * @package ZM\Annotation\Http
 * @Annotation
 * @Target("ALL")
 */
class Middleware extends AnnotationBase implements ErgodicAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $middleware;

    /**
     * @var string[]
     */
    public $params = [];
}
