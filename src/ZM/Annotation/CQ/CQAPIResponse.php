<?php

declare(strict_types=1);

namespace ZM\Annotation\CQ;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class CQAPIResponse
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class CQAPIResponse extends AnnotationBase
{
    /**
     * @var int
     * @Required()
     */
    public $retcode;

    public function __construct($retcode)
    {
        $this->retcode = $retcode;
    }
}
