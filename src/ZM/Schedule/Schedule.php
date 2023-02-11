<?php

declare(strict_types=1);

namespace ZM\Schedule;

use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Coroutine\CoroutineInterface;
use ZM\Annotation\Framework\Cron;

class Schedule
{
    private int $next_run = 0;

    /**
     * 正在执行的排程任务列表
     *
     * @var Cron[]
     */
    private array $executing = [];

    private bool $available;

    public function __construct()
    {
        $c = Adaptive::getCoroutine();
        $this->available = $c instanceof CoroutineInterface;
    }

    /**
     * 添加一个排程任务
     *
     * @param Cron $cron Cron 注解
     */
    public function addSchedule(Cron $cron): void
    {
        if (!$this->available) {
            $location = $cron->class === '' ? $cron->method : $cron->class . '::' . $cron->method;
            logger()->error('排程任务只能在协程环境下使用，排程任务 {location} 将不会被执行', ['location' => $location]);
            return;
        }
        $next_run = $cron->expression->getNextRunDate()->getTimestamp();
        // 防止在同一分钟内重复执行
        if ($next_run < $this->next_run) {
            $next_run = $this->next_run;
        }
        $this->next_run = $cron->expression->getNextRunDate()->getTimestamp();
        $wait_ms = max(0, ($next_run - time()) * 1000);
        Timer::after($wait_ms, function () use ($cron) {
            $this->dispatch($cron);
            $this->addSchedule($cron);
        });
    }

    public function dispatch(Cron $cron): void
    {
        // 检查是否重叠执行
        if ($cron->no_overlap && in_array($cron, $this->executing, true)) {
            return;
        }
        $this->executing[] = $cron;
        // 新建一个协程运行排程任务，避免阻塞
        Adaptive::getCoroutine()->create(function () use ($cron) {
            $callable = $cron->class === '' ? $cron->method : [$cron->class, $cron->method];
            container()->call($callable);
            $this->executing = array_diff($this->executing, [$cron]);
        });
    }
}
