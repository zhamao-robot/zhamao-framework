<?php

declare(strict_types=1);

namespace ZM\Utils\Manager;

use Cron\CronExpression;
use Doctrine\Common\Annotations\AnnotationException;
use Error;
use Exception;
use InvalidArgumentException;
use Swoole\Timer;
use ZM\Annotation\Cron\Cron;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Event\EventManager;
use ZM\Exception\InterruptException;
use ZM\Store\ZMAtomic;

class CronManager
{
    /**
     * 初始化 Cron 注解
     * 必须在 WorkerStart 事件中调用
     *
     * @throws Exception
     * @internal
     */
    public static function initCronTasks()
    {
        $dispatcher = new EventDispatcher(Cron::class);
        $all = EventManager::$events[Cron::class] ?? [];
        foreach ($all as $v) {
            /** @var Cron $v */
            if (server()->worker_id !== $v->worker_id && $v->worker_id != -1) {
                return;
            }
            try {
                if (strpos($v->expression, '\\') !== 0) {
                    $v->expression = str_replace('\\', '/', $v->expression);
                }
                $cron = new CronExpression($v->expression);
                $cron->setMaxIterationCount($v->max_iteration_count);
                $plain_class = $v->class;
                Console::debug("Cron task checker starting {$plain_class}:{$v->method}, next run at {$cron->getNextRunDate()->format('Y-m-d H:i:s')}");
                if ($v->check_delay_time > 60000 || $v->check_delay_time < 1000) {
                    Console::warning(zm_internal_errcode('E00076') . 'Delay time must be between 1000 and 60000, reset to 20000');
                    $v->check_delay_time = 20000;
                }
            } catch (InvalidArgumentException $e) {
                Console::error(zm_internal_errcode('E00075') . 'Invalid cron expression or arguments, please check it!');
                throw $e;
            }

            Timer::tick($v->check_delay_time, static function () use ($v, $dispatcher, $cron) {
                set_coroutine_params([]);
                if (ZMAtomic::get('stop_signal')->get() != 0) {
                    Timer::clearAll();
                    return;
                }
                try {
                    Console::debug('Cron: ' . ($cron->isDue() ? 'true' : 'false') . ', last: ' . $cron->getPreviousRunDate()->format('Y-m-d H:i:s') . ', next: ' . $cron->getNextRunDate()->format('Y-m-d H:i:s'));
                    if ($cron->isDue()) {
                        if ($v->getStatus() === 0) {
                            self::startExecute($v, $dispatcher, $cron);
                        } elseif ($v->getStatus() === 2) {
                            if ($v->getRecordNextTime() !== $cron->getNextRunDate()->getTimestamp()) {
                                self::startExecute($v, $dispatcher, $cron);
                            }
                        }
                    } else {
                        if ($v->getStatus() === 2 && $v->getRecordNextTime()) {
                            $v->setStatus(0);
                        }
                    }
                } catch (Exception $e) {
                    Console::error(zm_internal_errcode('E00034') . 'Uncaught error from Cron: ' . $e->getMessage() . ' at ' . $e->getFile() . "({$e->getLine()})");
                } catch (Error $e) {
                    Console::error(zm_internal_errcode('E00034') . 'Uncaught fatal error from Cron: ' . $e->getMessage());
                    echo Console::setColor($e->getTraceAsString(), 'gray');
                    Console::error('Please check your code!');
                }
            });
        }
    }

    /**
     * @throws InterruptException
     * @throws AnnotationException
     * @throws Exception
     */
    private static function startExecute(Cron $v, EventDispatcher $dispatcher, CronExpression $cron)
    {
        Console::verbose("Cron task {$v->class}:{$v->method} is due, running at " . date('Y-m-d H:i:s') . ($v->getRecordNextTime() === 0 ? '' : (', offset ' . (time() - $v->getRecordNextTime()) . 's')));
        $v->setStatus(1);
        $starttime = microtime(true);
        $pre_next_time = $cron->getNextRunDate()->getTimestamp();
        $dispatcher->dispatchEvent($v, null, $cron);
        Console::verbose("Cron task {$v->class}:{$v->method} is done, using " . round(microtime(true) - $starttime, 3) . 's');
        if ($pre_next_time !== $cron->getNextRunDate()->getTimestamp()) { // 这一步用于判断运行的Cron是否已经覆盖到下一个运行区间
            if (time() + round($v->check_delay_time / 1000) >= $pre_next_time) { // 假设检测到下一个周期运行时间已经要超过了预计的时间，则警告运行超时
                Console::warning(zm_internal_errcode('E00077') . 'Cron task ' . $v->class . ':' . $v->method . ' is timeout');
            }
        } else {
            Console::verbose('Next run at ' . date('Y-m-d H:i:s', $cron->getNextRunDate()->getTimestamp()));
        }
        $v->setRecordNextTime($pre_next_time);
        $v->setStatus(2);
    }
}
