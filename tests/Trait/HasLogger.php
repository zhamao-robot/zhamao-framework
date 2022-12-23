<?php

declare(strict_types=1);

namespace Tests\Trait;

use Prophecy\Prophet;
use Psr\Log\AbstractLogger;

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
        $logger->log()->will(function ($args) {
            $this->mockLog(...$args);
        });
        ob_logger_register($logger->reveal());
    }
}
