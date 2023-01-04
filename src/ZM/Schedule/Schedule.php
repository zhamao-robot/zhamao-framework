<?php

declare(strict_types=1);

namespace ZM\Schedule;

use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Coroutine\CoroutineInterface;
use ZM\Annotation\Framework\Cron;

class Schedule
{
    /**
     * 排程任务列表
     *
     * @var Cron[]
     */
    private array $schedules = [];

    /**
     * 正在执行的排程任务列表
     *
     * @var Cron[]
     */
    private array $executing = [];

    public function __construct()
    {
        $c = Adaptive::getCoroutine();
        if (!$c instanceof CoroutineInterface) {
            logger()->error('排程任务只能在协程环境下使用');
            return;
        }

        // 每秒检查一次，精度为一分钟，从最近的下一分钟开始
        $c->create(function () {
            /* @phpstan-ignore-next-line 协程会睡觉的，不会阻塞 */
            while (true) {
                $now = time();
                $this->run();
                $sleep_time = 60 - ($now % 60);
                Adaptive::sleep($sleep_time);
            }
        });
    }

    /**
     * 添加一个排程任务
     *
     * @param Cron $cron Cron 注解
     */
    public function addSchedule(Cron $cron): void
    {
        $this->schedules[] = $cron;
    }

    /**
     * 获取到期的排程任务
     *
     * @return Cron[]
     */
    public function due(): array
    {
        return array_filter($this->schedules, fn (Cron $cron) => $cron->expression->isDue());
    }

    /**
     * 运行排程任务
     */
    public function run(): void
    {
        // 同时运行到期的排程任务
        foreach ($this->due() as $cron) {
            // 检查是否重叠执行
            if ($cron->no_overlap && in_array($cron, $this->executing, true)) {
                continue;
            }
            $this->executing[] = $cron;
            // 新建一个协程运行排程任务，避免阻塞
            Adaptive::getCoroutine()->create(function () use ($cron) {
                $callable = [$cron->class, $cron->method];
                container()->call($callable);
                $this->executing = array_diff($this->executing, [$cron]);
            });
        }
    }
}
