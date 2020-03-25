<?php


namespace ZM\Annotation\Http;


use Doctrine\Common\Annotations\Annotation\Target;
use Exception;
use ZM\Annotation\AnnotationBase;

/**
 * Class HandleException
 * @package ZM\Annotation\Http
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