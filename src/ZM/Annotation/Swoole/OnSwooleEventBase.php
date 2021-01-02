<?php


namespace ZM\Annotation\Swoole;


use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;
use ZM\Annotation\Interfaces\Rule;

abstract class OnSwooleEventBase extends AnnotationBase implements Level, Rule
{
    /**
     * @var string
     */
    public $rule = "";
    /**
     * @var int
     */
    public $level = 20;

    /**
     * @return string
     */
    public function getRule(): string {
        return $this->rule !== "" ? $this->rule : true;
    }

    /**
     * @param string $rule
     */
    public function setRule(string $rule) {
        $this->rule = $rule;
    }

    /**
     * @return int
     */
    public function getLevel(): int {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel(int $level) {
        $this->level = $level;
    }
}