<?php

declare(strict_types=1);

namespace ZM\Annotation\CQ;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class CQAPIResponse
 * @Annotation
 * @Target("METHOD")
 */
class CQAPIResponse extends AnnotationBase
{
    /**
     * @var int
     * @Required()
     */
    public $retcode;
}
