<?php


namespace ZM\Annotation\Http;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class Controller
 * @Annotation
 * @Target("CLASS")
 * @package ZM\Annotation\Http
 */
class Controller extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $prefix = '';
}