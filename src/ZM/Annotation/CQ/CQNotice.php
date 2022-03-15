<?php

declare(strict_types=1);

namespace ZM\Annotation\CQ;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQNotice
 * @Annotation
 * @Target("ALL")
 */
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
