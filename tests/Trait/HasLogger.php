<?php

declare(strict_types=1);

namespace Tests\Trait;

use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * 模拟 Logger 行为
 * @property Prophet $prophet
 */
trait HasLogger
{
    private array $logs = [];

    private function mockLog($level, $message, array $context = []): void
    {
        $this->logs[] = compact('level', 'message', 'context');
    }

    private function logged($level, $message, array $context = []): bool
    {
        return in_array(compact('level', 'message', 'context'), $this->logs, true);
    }

    private function assertLogged($level, $message = null, array $context = []): void
    {
        $this->assertTrue(
            $this->logged($level, $message, $context),
            "Failed asserting that the log contains [{$level}] {$message}"
        );
    }

    private function startMockLogger(): void
    {
        $logger = $this->prophet->prophesize(AbstractLogger::class);
        $levels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];
        $log_it = fn (...$args) => $this->mockLog(...$args);
        foreach ($levels as $level) {
            $logger->{$level}(Argument::type('string'), Argument::any())->will(fn ($args) => $log_it($level, ...$args));
        }
        $logger->log(Argument::in($levels), Argument::type('string'), Argument::any())->will(fn ($args) => $log_it(...$args));
        ob_logger_register($logger->reveal());
    }
}
