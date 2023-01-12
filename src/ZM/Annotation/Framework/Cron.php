<?php

declare(strict_types=1);

namespace ZM\Annotation\Framework;

use Cron\CronExpression;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class Cron extends AnnotationBase
{
    public CronExpression $expression;

    /**
     * @param string $expression Cron 表达式
     * @param int    $worker_id  Worker ID
     * @param bool   $no_overlap 是否不允许重叠执行
     */
    public function __construct(
        string $expression,
        public int $worker_id = 0,
        public bool $no_overlap = false
    ) {
        $this->expression = new CronExpression($expression);
    }

    public static function make(string $expression, int $worker_id = 0, bool $no_overlap = false): Cron
    {
        return new Cron($expression, $worker_id, $no_overlap);
    }
}
