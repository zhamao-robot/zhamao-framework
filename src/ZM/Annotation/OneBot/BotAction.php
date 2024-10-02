<?php

declare(strict_types=1);

namespace ZM\Annotation\OneBot;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Level;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BotAction extends AnnotationBase implements Level
{
    public function __construct(public string $action = '', public bool $need_response = false, public int $level = 20) {}

    public static function make(callable $callback, string $action, bool $need_response = false, int $level = 20): BotAction
    {
        $action = new BotAction($action, $need_response, $level);
        $action->on($callback);
        return $action;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }
}
