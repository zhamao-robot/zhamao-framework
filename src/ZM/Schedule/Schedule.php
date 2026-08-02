<?php

declare(strict_types=1);

namespace ZM\Schedule;

use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Coroutine\CoroutineInterface;
use ZM\Annotation\Framework\Cron;

class Schedule
{
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
        // 每个 Cron 各自独立计算下一次运行时间，互不干扰
        $next_run = $cron->expression->getNextRunDate()->getTimestamp();
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
            try {
                $callable = $cron->class === '' ? $cron->method : [$cron->class, $cron->method];
                container()->call($callable);
            } finally {
                // 无论任务是否抛异常，都要从执行列表中移除，避免 no_overlap 永久失效
                $index = array_search($cron, $this->executing, true);
                if ($index !== false) {
                    unset($this->executing[$index]);
                }
            }
        });
    }
}
