<?php

declare(strict_types=1);

namespace ZM\Annotation\Http;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\ErgodicAnnotation;

/**
 * Class Middleware
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
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

    public function __construct($middleware, $params = [])
    {
        $this->middleware = $middleware;
        $this->params = $params;
    }
}
