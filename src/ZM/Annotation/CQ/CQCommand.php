<?php

declare(strict_types=1);

namespace ZM\Annotation\CQ;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class CQCommand
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("ALL")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class CQCommand extends AnnotationBase implements Level
{
    /** @var string */
    public $match = '';

    /** @var string */
    public $pattern = '';

    /** @var string */
    public $regex = '';

    /** @var string */
    public $start_with = '';

    /** @var string */
    public $end_with = '';

    /** @var string */
    public $keyword = '';

    /** @var string[] */
    public $alias = [];

    /** @var string */
    public $message_type = '';

    /** @var int */
    public $user_id = 0;

    /** @var int */
    public $group_id = 0;

    /** @var int */
    public $discuss_id = 0;

    /** @var int */
    public $level = 20;

    public function __construct($match = '', $pattern = '', $regex = '', $start_with = '', $end_with = '', $keyword = '', $alias = [], $message_type = '', $user_id = 0, $group_id = 0, $discuss_id = 0, $level = 20)
    {
        $this->match = $match;
        $this->pattern = $pattern;
        $this->regex = $regex;
        $this->start_with = $start_with;
        $this->end_with = $end_with;
        $this->keyword = $keyword;
        $this->alias = $alias;
        $this->message_type = $message_type;
        $this->user_id = $user_id;
        $this->group_id = $group_id;
        $this->discuss_id = $discuss_id;
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
