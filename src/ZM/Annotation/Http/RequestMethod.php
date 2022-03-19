<?php

declare(strict_types=1);

namespace ZM\Annotation\Http;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class RequestMethod
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class RequestMethod extends AnnotationBase
{
    public const GET = 'GET';

    public const POST = 'POST';

    public const PUT = 'PUT';

    public const PATCH = 'PATCH';

    public const DELETE = 'DELETE';

    public const OPTIONS = 'OPTIONS';

    public const HEAD = 'HEAD';

    /**
     * @var string
     * @Required()
     */
    public $method = self::GET;

    public function __construct($method)
    {
        $this->method = $method;
    }
}
