<?php


namespace ZM\Annotation\Http;

use Doctrine\Common\Annotations\Annotation\Required;
use ZM\Annotation\AnnotationBase;

/**
 * Class RequestMethod
 * @Annotation
 *
 * @package ZM\Annotation\Http
 */
class RequestMethod extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $method = self::GET;

    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';
    public const OPTIONS = 'OPTIONS';
    public const HEAD = 'HEAD';
}