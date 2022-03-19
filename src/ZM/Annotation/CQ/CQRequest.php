<?php

declare(strict_types=1);

namespace ZM\Annotation\CQ;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQRequest
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class CQRequest extends AnnotationBase implements Level
{
    /** @var string */
    public $request_type = '';

    /** @var string */
    public $sub_type = '';

    /** @var int */
    public $user_id = 0;

    /** @var string */
    public $comment = '';

    /** @var int */
    public $level = 20;

    public function __construct($request_type = '', $sub_type = '', $user_id = 0, $comment = '', $level = 20)
    {
        $this->request_type = $request_type;
        $this->sub_type = $sub_type;
        $this->user_id = $user_id;
        $this->comment = $comment;
        $this->level = $level;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }
}
