<?php


namespace ZM\Annotation\Http;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\ErgodicAnnotation;

/**
 * Class Controller
 * @Annotation
 * @Target("CLASS")
 * @package ZM\Annotation\Http
 */
class Controller extends AnnotationBase implements ErgodicAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $prefix = '';
}
