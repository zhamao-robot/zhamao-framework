<?php


namespace ZM\Annotation\Http;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class RequestMapping
 * @Annotation
 * @Target("ALL")
 * @package ZM\Annotation\Http
 */
class RequestMapping extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $route = '';

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var array
     */
    public $request_method = [RequestMethod::GET, RequestMethod::POST];

    /**
     * Routing path params binding. eg. {"id"="\d+"}
     * @var array
     */
    public $params = [];
}