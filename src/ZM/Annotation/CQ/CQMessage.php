<?php

declare(strict_types=1);

namespace ZM\Annotation\CQ;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQMessage
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("ALL")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class CQMessage extends AnnotationBase implements Level
{
    /**
     * @var string
     */
    public $message_type = '';

    /** @var int */
    public $user_id = 0;

    /** @var int */
    public $group_id = 0;

    /** @var int */
    public $discuss_id = 0;

    /** @var string */
    public $message = '';

    /** @var string */
    public $raw_message = '';

    /** @var int */
    public $level = 20;

    public function __construct($message_type = '', $user_id = 0, $group_id = 0, $discuss_id = 0, $message = '', $raw_message = '', $level = 20)
    {
        $this->message_type = $message_type;
        $this->user_id = $user_id;
        $this->group_id = $group_id;
        $this->discuss_id = $discuss_id;
        $this->message = $message;
        $this->raw_message = $raw_message;
        $this->level = $level;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }
}
