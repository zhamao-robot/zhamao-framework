<?php

declare(strict_types=1);

namespace ZM\Annotation\Cron;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Cron extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $expression;

    /**
     * @var int
     */
    public $worker_id = 0;

    /**
     * @var int
     */
    public $check_delay_time = 20000;

    /**
     * @var int
     */
    public $max_iteration_count = 1000;

    /**
     * @var int Cron执行状态
     */
    private $status = 0;

    private $record_next_time = 0;

    public function __construct(string $expression, int $worker_id = 0, int $check_delay_time = 20000, int $max_iteration_count = 1000)
    {
        $this->expression = $expression;
        $this->worker_id = $worker_id;
        $this->check_delay_time = $check_delay_time;
        $this->max_iteration_count = $max_iteration_count;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @internal
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @internal
     */
    public function getRecordNextTime(): int
    {
        return $this->record_next_time;
    }

    /**
     * @internal
     */
    public function setRecordNextTime(int $record_next_time): void
    {
        $this->record_next_time = $record_next_time;
    }
}
