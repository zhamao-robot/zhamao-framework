<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Rule;

/**
 * Class OnTask
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class OnTask extends AnnotationBase implements Rule
{
    /**
     * @var string
     * @Required()
     */
    public $task_name;

    /**
     * @var string
     */
    public $rule = '';

    public function __construct($task_name, $rule = '')
    {
        $this->task_name = $task_name;
        $this->rule = $rule;
    }

    /**
     * @return string 返回规则语句
     */
    public function getRule(): string
    {
        return $this->rule;
    }
}
