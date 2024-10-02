<?php

declare(strict_types=1);

namespace ZM\Annotation\Framework;

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
     * @param string $event_class 绑定事件的类型
     */
    public function __construct(
        /**
         * @Required()
         */
        public string $event_class,
        public int $level = 800
    ) {}

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }
}
