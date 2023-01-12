<?php

namespace ZM\Plugin\Traits;

use ZM\Annotation\Framework\Cron;

trait CronTrait
{
    /** @var Cron[] 计划任务列表 */
    protected array $crons = [];

    /**
     * 添加一个计划任务
     *
     * @param Cron $cron 计划任务注解对象
     * @return void
     */
    public function addCron(Cron $cron): void
    {
        $this->crons[] = $cron;
    }

    /**
     * @internal
     * @return array
     */
    public function getCrons(): array
    {
        return $this->crons;
    }
}
