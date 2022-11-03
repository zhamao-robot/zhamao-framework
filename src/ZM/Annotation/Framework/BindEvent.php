<?php

declare(strict_types=1);

namespace ZM\Annotation\Framework;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * Class BindEvent
 * 通过注解绑定 EventProvider 支持的事件
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 * @since 3.0.0
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class BindEvent extends AnnotationBase implements Level
{
    /**
     * @var string
     * @Required()
     */
    public $event_class;

    /** @var int */
    public $level = 800;

    /**
     * @param string $event_class 绑定事件的类型
     */
    public function __construct(string $event_class, int $level = 800)
    {
        $this->event_class = $event_class;
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
