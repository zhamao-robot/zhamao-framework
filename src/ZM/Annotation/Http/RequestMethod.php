<?php

declare(strict_types=1);

namespace ZM\Annotation\Http;

use Doctrine\Common\Annotations\Annotation\Required;
use ZM\Annotation\AnnotationBase;

/**
 * Class RequestMethod
 * @Annotation
 */
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
}
