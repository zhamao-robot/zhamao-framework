<?php


namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\Interfaces\Rule;

/**
 * Class OnTask
 * @package ZM\Annotation\Swoole
 * @Annotation
 * @Target("METHOD")
 */
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
    public $rule = "";

    /**
     * @return mixed
     */
    public function getRule() {
        return $this->rule;
    }
}