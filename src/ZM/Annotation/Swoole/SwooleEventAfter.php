<?php


namespace ZM\Annotation\Swoole;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;
use ZM\Annotation\Interfaces\Rule;

/**
 * Class SwooleEventAfter
 * @Annotation
 * @Target("ALL")
 * @package ZM\Annotation\Swoole
 */
class SwooleEventAfter extends AnnotationBase implements Rule, Level
{
    /**
     * @var string
     * @Required
     */
    public $type;

    /** @var string */
    public $rule = "";

    /** @var int */
    public $level = 20;

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getRule(): string {
        return $this->rule;
    }

    /**
     * @param string $rule
     */
    public function setRule(string $rule): void {
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
    public function setLevel(int $level): void {
        $this->level = $level;
    }


}