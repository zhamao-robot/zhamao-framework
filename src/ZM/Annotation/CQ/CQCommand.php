<?php


namespace ZM\Annotation\CQ;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQCommand
 * @Annotation
 * @Target("ALL")
 * @package ZM\Annotation\CQ
 */
class CQCommand extends AnnotationBase implements Level
{
    /** @var string */
    public $match = "";
    /** @var string */
    public $pattern = "";
    /** @var string */
    public $regex = "";
    /** @var string */
    public $start_with = "";
    /** @var string */
    public $end_with = "";
    /** @var string[] */
    public $alias = [];
    /** @var string */
    public $message_type = "";
    /** @var int */
    public $user_id = 0;
    /** @var int */
    public $group_id = 0;
    /** @var int */
    public $discuss_id = 0;
    /** @var int */
    public $level = 20;

    /**
     * @return int
     */
    public function getLevel(): int { return $this->level; }

    /**
     * @param int $level
     */
    public function setLevel(int $level) { $this->level = $level; }

}
