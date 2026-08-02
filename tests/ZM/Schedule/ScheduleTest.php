<?php

declare(strict_types=1);

namespace Tests\ZM\Schedule;

use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Coroutine\CoroutineInterface;
use Prophecy\Argument;
use Tests\TestCase;
use ZM\Annotation\Framework\Cron;
use ZM\Schedule\Schedule;

/**
 * @internal
 */
class ScheduleTest extends TestCase
{
    private $co;

    protected function setUp(): void
    {
        parent::setUp();
        $this->co = $this->prophesize(CoroutineInterface::class);
        $this->co->isAvailable()->willReturn(true);
        $this->co->getCid()->willReturn(1);
        // 同步执行 create 中传入的回调，便于断言 try/finally 的行为
        $this->co->create(Argument::type('callable'))->will(function (array $args) {
            $args[0]();
            return 1;
        });
        $ref = new \ReflectionProperty(Adaptive::class, 'coroutine');
        $ref->setValue(null, $this->co->reveal());
    }

    protected function tearDown(): void
    {
        $ref = new \ReflectionProperty(Adaptive::class, 'coroutine');
        $ref->setValue(null, null);
        parent::tearDown();
    }

    public function testDispatchRemovesExecutingWhenCallableThrows(): void
    {
        $schedule = new Schedule();
        $cron = new Cron('* * * * *');
        $cron->class = ScheduleThrowingClass::class;
        $cron->method = 'throwException';
        $cron->no_overlap = true;

        try {
            $schedule->dispatch($cron);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('schedule task boom', $e->getMessage());
        }
        // 即使抛异常，也要保证任务被从执行列表中移除
        $this->assertSame([], $this->getExecuting($schedule));
    }

    public function testNoOverlapNotPermanentlyBlockedAfterException(): void
    {
        $schedule = new Schedule();
        $throwing = new Cron('* * * * *');
        $throwing->class = ScheduleThrowingClass::class;
        $throwing->method = 'throwException';
        $throwing->no_overlap = true;

        try {
            $schedule->dispatch($throwing);
        } catch (\RuntimeException) {
            // 预期异常
        }
        $this->assertSame([], $this->getExecuting($schedule));

        // 异常清理后，no_overlap 的任务应能再次被正常派发执行
        ScheduleCountingClass::$count = 0;
        $counting = new Cron('* * * * *');
        $counting->class = ScheduleCountingClass::class;
        $counting->method = 'count';
        $counting->no_overlap = true;
        $schedule->dispatch($counting);
        $schedule->dispatch($counting);
        $this->assertSame(2, ScheduleCountingClass::$count);
        $this->assertSame([], $this->getExecuting($schedule));
    }

    public function testDispatchRunsCallableAndClearsExecuting(): void
    {
        $schedule = new Schedule();
        ScheduleCountingClass::$count = 0;
        $cron = new Cron('* * * * *');
        $cron->class = ScheduleCountingClass::class;
        $cron->method = 'count';

        $schedule->dispatch($cron);

        $this->assertSame(1, ScheduleCountingClass::$count);
        $this->assertSame([], $this->getExecuting($schedule));
    }

    private function getExecuting(Schedule $schedule): array
    {
        $ref = new \ReflectionProperty(Schedule::class, 'executing');
        return $ref->getValue($schedule);
    }
}

class ScheduleThrowingClass
{
    public static function throwException(): void
    {
        throw new \RuntimeException('schedule task boom');
    }
}

class ScheduleCountingClass
{
    public static int $count = 0;

    public static function count(): void
    {
        ++self::$count;
    }
}
