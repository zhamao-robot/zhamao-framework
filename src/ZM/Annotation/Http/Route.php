<?php

declare(strict_types=1);

namespace ZM\Annotation\Http;

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
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class Route extends AnnotationBase
{
    public function __construct(
        /**
         * @Required()
         */
        public $route,
        public $name = '',
        public $request_method = ['GET', 'POST'],
        /**
         * Routing path params binding. eg. {"id"="\d+"}
         */
        public $params = []
    ) {}

    public static function make($route, $name = '', $request_method = ['GET', 'POST'], $params = []): static
    {
        return new static($route, $name, $request_method, $params);
    }
}
