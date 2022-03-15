<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;
use ZM\Annotation\Interfaces\Rule;

abstract class OnSwooleEventBase extends AnnotationBase implements Level, Rule
{
    /**
     * @var string
     */
    public $rule = '';

    /**
     * @var int
     */
    public $level = 20;

    public function getRule()
    {
        return $this->rule !== '' ? $this->rule : true;
    }

    public function setRule(string $rule)
    {
        $this->rule = $rule;
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
