<?php

declare(strict_types=1);

namespace ZM\Annotation\CQ;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQNotice
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class CQNotice extends AnnotationBase implements Level
{
    /** @var string */
    public $notice_type = '';

    /** @var string */
    public $sub_type = '';

    /** @var int */
    public $group_id = 0;

    /** @var int */
    public $operator_id = 0;

    /** @var int */
    public $level = 20;

    public function __construct($notice_type = '', $sub_type = '', $group_id = 0, $operator_id = 0, $level = 20)
    {
        $this->notice_type = $notice_type;
        $this->sub_type = $sub_type;
        $this->group_id = $group_id;
        $this->operator_id = $operator_id;
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
