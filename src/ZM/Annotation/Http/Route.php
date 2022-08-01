<?php

declare(strict_types=1);

namespace ZM\Annotation\Http;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class RequestMapping
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class Route extends AnnotationBase
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
    public $request_method = ['GET', 'POST'];

    /**
     * Routing path params binding. eg. {"id"="\d+"}
     * @var array
     */
    public $params = [];

    public function __construct($route, $name = '', $request_method = ['GET', 'POST'], $params = [])
    {
        $this->route = $route;
        $this->name = $name;
        $this->request_method = $request_method;
        $this->params = $params;
    }
}
