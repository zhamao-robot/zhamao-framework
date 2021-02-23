<?php


namespace ZM\Annotation\CQ;

use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQMessage
 * @Annotation
 * @Target("ALL")
 * @package ZM\Annotation\CQ
 */
class CQMessage extends AnnotationBase implements Level
{
    /**
     * @var string
     */
    public $message_type = "";
    /** @var int */
    public $user_id = 0;
    /** @var int */
    public $group_id = 0;
    /** @var int */
    public $discuss_id = 0;
    /** @var string */
    public $message = "";
    /** @var string */
    public $raw_message = "";
    /** @var int */
    public $level = 20;

    public function getLevel() { return $this->level; }

    public function setLevel(int $level) {
        $this->level = $level;
    }
}